<?php

    /**
    *   Navigation widget used in the academic section
    */
    class NavWidget
    {
        const OPTION = 'academic_nav_widget';

        public $links = false;
        public $walker_current_link = false;
        public $delete_walker_parent = 0;
        public $delete_walker_parents = array();

        /**
        * Construct the NavWidget collection
        * @constructor
        */
        public function __construct()
        {
            $this->links =  $this->get_links();
	        //wp_die( sprintf( '<pre>%s</pre>', print_r( $this, 1 ) ) );
        }

        /**
        * get links
        * @return array<NavWidgetLink>
        */
        public function get_links()
        {
            if( ! $this->links )
            {
                if( !function_exists('get_option') ||
                    !( $links = get_option( apply_filters( 'nav_widget_option', self::OPTION ) ) ) )
                {
                    return array();
                }
                return $links;
            }

            return $this->links;
        }

        /**
        * display the admin area
        *
        */
        public static function admin()
        {
            HRHarvardTheme::get_template_part( 'admin', 'nav-widget' );
        }

        /**
        * save links
        *
        */
        public function save()
        {
            update_option( self::OPTION, $this->links );
        }

        /**
        * get link
        *
        * @deprecated - use get_link_by_id instead
        * @param mixed $id
         * @return bool|\NavWidgetLink
         */
        public function get_link( $id )
        {
            return $this->get_link_by_id($id);
        }

        /**
        * recursive function to iterate through links and their children to fetch the link matching the current id.
        * used in a loop of top level links. see self::get_link()
        *
        * @deprecated - use get_link_by_id
        * @param mixed $id
        * @param mixed $link
         * @return bool|\NavWidgetLink
         */
        public function get_link_walker( $id, $link )
        {
            return $this->get_link_by_id($id, $link);
        }

        /**
        * recursive function to iterate through links and their children to fetch the link matching the current id.
        * used in a loop of top level links. see self::get_link()
        *
        * @param string $id
        * @param NavWidgetLink $link
        * @return NavWidgetLink|bool false if not found
        */
        public function get_link_by_id( $id, NavWidgetLink $link = null )
        {
            if( is_null( $link ) ){
                $links = $this->links;
            }
            else
            {
                if( $id == $link->get_id() ){
                    return $link;
                }

                $links = $link->get_children();
            }

            foreach( $links as $child )
            {
                if( $id == $child->get_id() ){
                    return $child;
                }

                if( $found = $this->get_link_by_id( $id, $child ) ){
                    return $found;
                }
            }

            return false;
        }

        /**
        * add link
        *
        * @param NavWidgetLink $link
        * @return void
        */
        public function add_link(NavWidgetLink $link)
        {
            $this->links[ $link->get_id() ] = $link;
        }

        /**
        * Move a link to a new parent
        *
        * @param NavWidgetLink $link the link to move (by reference because we clone it and it breaks the original reference)
        * @param null|string|NavWidgetLink $parent the new parent (null/false moves to top level)
        * @return bool true on successful move
        */
        public function move_link_to_parent(NavWidgetLink &$link, $parent)
        {
            if ($parent &&
                !($parent instanceof NavWidgetLink))
            {
                // if we have a parent, and that parent is not a NavWidgetLink we need to find the respective NavWidgetLink for the parent id
                $parent = $this->get_link_by_id($parent);
                if (!($parent instanceof NavWidgetLink))
                {
                    // could not find a parent link
                    return false;
                }
            }
            elseif (!$parent)
            {
                // make sure the value is null if we got a null/false value (false value could be empty array, empty string, 0, false, null)
                $parent = null;
            }

            // make sure the parent-id is different than the link's id
            if ((!$parent && !$link->get_parent_ID()) ||
                 ($parent && $parent->get_id() == $link->get_parent_ID()))
            {
                // nothing to do, the parent-id is the same
                return true;
            }
	        elseif( $parent && $link->is_child( $parent ) )
	        {
		        // cannot set parent id to child
		        return false;
	        }

            // clone the link so we are working with a fresh object reference
            $link = clone $link;

            // delete the link (this removes the link from the child tree it currently resides in)
            if ($this->delete_link($link->get_id()))
            {
                if ($parent)
                {
                    // add the link into the parent's child tree
                    $parent->add_child_link($link);
                }
                else
                {
                    // add the link into the root tree
                    $link->set_parent_ID(null);
                    $this->add_link($link);
                }
                return true;
            }

            return false;
        }

        /**
        * edit link
        *
        * @deprecated - just edit the link yourself and call NavWidget::save()
        * @param mixed $id
        * @param mixed $url
        * @param mixed $text
        * @param mixed $open_in_new_window
         * @return bool
         */
        public function edit_link( $id, $url, $text, $open_in_new_window )
        {

            if( ! array_key_exists( $id, $this->links ) )
                return false;

            $this->links[ $id ]->set_url( $url );
            $this->links[ $id ]->set_text( $text );
            $this->links[ $id ]->set_open_in_new_window( $open_in_new_window );

            $this->save();
        }

        /**
        * delete link
        *
        * @param mixed $id
        * @return bool
        */
        public function delete_link( $id )
        {
            if( $this->has_links() )
                return $this->delete_link_walker( $id );

            return false;
        }

        /**
        * recursive function to iterate through links and their children to fetch the link matching the current id.
        * used in a loop of top level links. see self::get_link()
        *
        * @param mixed $id
        * @return bool
        */
        public function delete_link_walker( $id )
        {
            if (array_key_exists( $id, $this->links ))
            {
                unset( $this->links[ $id ] );
                return true;
            }
            else
            {
                foreach ($this->get_links() as $link)
                {
                    if ($link->delete_child($id))
                    {
                        return true;
                    }
                }
            }
            return false;
        }

        /**
        * check if there's links or not based on the size of the array
        *
        */
        public function has_links(){
            return count( $this->links ) == 0 ? false : true;
        }

        /**
        * build list items to be output in admin interface with edit forms for each
        *
        * @param NavWidgetLink $link
        */
        public function build_link_admin_li( NavWidgetLink $link )
        {
            ?>
            <li class="nav-widget-link">
                <div class="title-bar">
                    <span class="title"><?php echo $link->get_text(); ?></span>
                    <a href="javascript://" class="edit-link">Edit</a>
                </div>
                <div class="edit-wrap" id="edit-wrap-<?php echo $link->get_id(); ?>">
                    <form action="#widget" method="post">
                        <p>
                            <input type="hidden" value="<?php echo wp_create_nonce( 'edit-link-' . $link->get_id() ); ?>" name="edit_nav_menu_link[nonce]" />
                            <input type="hidden" value="<?php echo $link->get_id(); ?>" name="edit_nav_menu_link[id]" />
                            <label for="link-text-<?php echo $link->get_id(); ?>">Text:</label>
                            <input id="link-text-<?php echo $link->get_id(); ?>" type="text" class="widefat" name="edit_nav_menu_link[text]" value="<?php echo $link->get_text(); ?>" />
                        </p>
                        <p>
                            <label for="link-url-<?php echo $link->get_id(); ?>">URL:</label>
                            <input id="link-url-<?php echo $link->get_id(); ?>" type="text" class="widefat" name="edit_nav_menu_link[url]" value="<?php echo $link->get_url(); ?>" />
                        </p>
                        <p>
                            <input type="checkbox" name="edit_nav_menu_link[open_in_new_window]" <?php if( $link->open_in_new_window() ) echo checked( 1, 1 ); ?> value="1" id="link-open-in-new-window-<?php echo $link->get_id(); ?>" />
                            <label for="link-open-in-new-window-<?php echo $link->get_id(); ?>">Open Link In New Window:</label>
                        </p>
                        <p>
                            <label for="link-parent-<?php echo $link->get_id(); ?>">Parent</label>
                            <?php echo $this->build_parent_link_select( 'edit_nav_menu_link', $link ); ?>
                        </p>
                        <p class="submit">
                            <input type="submit" value="Save Changes" class="button-primary submit-butotn" />
                            <input type="button" value="Cancel" class="button-secondary cancel-button" rel="edit-wrap-<?php echo $link->get_id(); ?>" />
                        </p>
                    </form>
                    <form action="#widget" method="post" class="del-link-form">
                        <input type="hidden" name="delete_nav_widget_link[nonce]" value="<?php echo wp_create_nonce( 'delete-link-' . $link->get_id() ); ?>" />
                        <input type="hidden" name="delete_nav_widget_link[id]" value="<?php echo $link->get_id(); ?>" />
                        <input type="submit" class="delete-btn button-secondary" value="Delete" />
                    </form>
                </div>
                <?php if( $link->has_children() ) : ?>
                    <ul class="children">
                        <?php
                            // recursion
                            foreach( $link->get_children() as $child ){
                                $this->build_link_admin_li( $child );
                            }
                        ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php
        }

	    /**
	     * loop through links and children outputting option tags for a select tag
	     *
	     * @param mixed $link
	     * @param NavWidgetLink $parent_link
	     * @param mixed $selected
	     * @return string
	     */
        public function link_select_option( NavWidgetLink $link, NavWidgetLink $parent_link = null, $selected = false )
        {
            // do not show current link or children of current link.
	        if( $parent_link && $parent_link->get_id() == $link->get_id() )
	        {
		        return '';
	        }

	        $out = '';
	        $select = $selected ? ' selected="selected"' : '';

            $out .= sprintf( '<option value="%s"%s>%s</option>', $link->get_id(), $select, /*(int)( $parent_link && $parent_link->get_parent_ID() == $link->get_id() ) ,*/$link->get_text() );

            if( $link->has_children() )
            {
                foreach( $link->get_children() as $child )
                {
                    if( $parent_link &&
						$parent_link->get_id() == $link->get_id() )
					{
						$out .= $this->link_select_option( $child, $parent_link, 1 );
					}
					else
					{
						$out .= $this->link_select_option( $child, $parent_link );
					}
                }
            }

	        return $out;
        }

        /**
        * build parent select drop-down for admin edit fields
        *
        * @param mixed $name
        * @param NavWidgetLink|bool(false) $parent_link
         * @return string
         */
        public function build_parent_link_select( $name, NavWidgetLink $parent_link = null )
        {
	        $out = '';

            if( $this->has_links() )
            {

                $out .= sprintf( '<select name="%s[parent]" id="link-parent%s" >', $name, $parent_link ? '-'.$parent_link->get_id() : '' );
	            $out .= sprintf( '<option value="0"%s>No Parent</option>', (!$parent_link || !$parent_link->get_parent_ID()) ? ' selected="selected"' : '' );

	            $out .= $this->build_parent_link_options( $parent_link );

	            $out .= sprintf( '</select>' );
            }

	        return $out;
        }

	    /**
	     * @param NavWidgetLink $parent_link
	     * @return string
	     */
	    public function build_parent_link_options( NavWidgetLink $parent_link = null )
	    {
		    $out = '';

		    foreach( $this->get_links() as $link )
		    {
			    /* @var $link NavWidgetLink */
			    if( $parent_link )
			    {
				    if( ! $parent_link->is_child( $link ) )
				    {
						$out .= $this->link_select_option( $link, $parent_link, ( $parent_link->get_parent_id() == $link->get_id() ) );
				    }
			    }
			    else
			    {
				    $out .= $this->link_select_option( $link, null, false );
			    }
		    }

		    return $out;
	    }
    }