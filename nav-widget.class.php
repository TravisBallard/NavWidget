<?php

    /**
    *   Navigation widget used in the academic section
    */
    class NavWidget
    {
        public static $option;

        public $links = false;
        public $walker_current_link = false;
        public $delete_walker_parent = 0;
        public $delete_walker_parents = array();

        /**
        * Construct the NavWidget collection
        * @constructor
        */
        public function __construct( $option = 'academic_nav_widget' )
        {
	        self::$option = $option;
            $this->links =  $this->get_links();
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
                    !( $links = get_option( apply_filters( 'nav_widget_option', self::$option ) ) ) )
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
            update_option( self::$option, $this->links );
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
	     * @param NavWidgetLink $parent
	     */
        public function build_link_admin_li( NavWidgetLink $link, NavWidgetLink $parent = null )
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
                            <?php
                                echo $this->build_parent_link_select( 'edit_nav_menu_link', $link );
                            ?>
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
                                $this->build_link_admin_li( $child, $link );
                            }
                        ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php
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
	        if( $parent_link )
	            return $this->buildSelectBox( $name, $parent_link,  $parent_link->get_parent_ID() );
	        else
		        return $this->buildSelectBox( $name, $parent_link );
        }


	    /**
	     * Get parent links only
	     * @return array<NavWidgetLink>
	     */
	    public function get_parent_links()
	    {
		    $parent = array();
		    foreach ($this->get_links() as $link)
		    {
			    if (!$link->get_parent_id())
			    {
				    $parent[] = $link;
			    }
		    }
		    return $parent;
	    }


	    /**
	     * Generate a select box from an array NavWidgetLinks
	     * @param $name
	     * @param NavWidgetLink $parent_link
	     * @param null $selectedId
	     * @param array $attributes attribute list to apply to the select box
	     * @param array $instructionOption instruction option to add to the select box (assoc array with text and value keys)
	     * @return string
	     */
	    public function buildSelectBox( $name, NavWidgetLink $parent_link=null, $selectedId=null, array $attributes=array(), array $instructionOption=array())
	    {
		    $data = $this->buildComboBox( $parent_link, $this->get_parent_links(), $selectedId);

		    $dom = new \DOMDocument;
		    $select = $dom->createElement('select');
		    $select->setAttribute( 'name', sprintf( '%s[parent]', $name ) );

		    foreach ($attributes as $attrName => $attrValue)
		    {
			    $select->setAttribute($attrName, $attrValue);
		    }

		    if (count($instructionOption) > 0)
		    {
			    if (isset($instructionOption['text']))
			    {
				    $option = $dom->createElement('option');
				    $option->appendChild( $dom->createTextNode($instructionOption['text']) );
				    if (isset($instructionOption['value']))
				    {
					    $option->setAttribute('value', $instructionOption['value']);
				    }
				    $select->appendChild($option);
			    }
		    }

		    // add No Parent item to lists
		    $option = $dom->createElement('option');
		    $option->appendChild( $dom->createTextNode( 'No Parent') );
			$option->setAttribute('value', 0);

		    $select->appendChild($option);

		    foreach ($data as $node)
		    {
			    $option = $dom->createElement('option');
			    $option->setAttribute('value', $node['value']);
			    $option->appendChild( $dom->createTextNode($node['text']));
			    if (isset($node['selected']) && $node['selected'])
			    {
				    $option->setAttribute('selected', 'selected');
			    }
			    $select->appendChild( $option );
		    }

		    $dom->appendChild($select);
		    return $dom->saveHTML();
	    }


	    /**
	     * Generate an array of options for a combobox, from a collection of NavWidgetLink nodes
	     *
	     * @param NavWidgetLink $parent_link
	     * @param array $entities array of NavWidgetLink nodes
	     * @param string $selectedId
	     * @param string $breadcrumb
	     * @throws Exception
	     * @return array
	     */
	    public function buildComboBox( NavWidgetLink $parent_link = null, array $entities, $selectedId=null, $breadcrumb='')
	    {
		    $dataArray = array();

		    if (strlen(trim($breadcrumb)) > 0)
		    {
			    $breadcrumb .= ' > ';
		    }

		    foreach ( $entities as $entity )
		    {
			    if (!($entity instanceof NavWidgetLink))
			    {
				    throw new \Exception(__CLASS__ .'::buildComboBox() encountered a node that does not implement NavWidgetLink');
				    break;
			    }
			    elseif( $parent_link && ( $parent_link->get_id() == $entity->get_id() || $parent_link->is_child( $entity ) ) )
			    {
				    continue;
			    }

			    $dataName = $breadcrumb . $entity->get_text();
			    $option = array('value'=>$entity->get_id(), 'text'=>$dataName);

			    if ($selectedId && $selectedId == $entity->get_id())
			    {
				    $option['selected'] = true;
			    }

			    $dataArray[] = $option;

			    $entityChildren = $entity->get_children();
			    if (count($entityChildren) > 0)
			    {
				    // recursion
				    $childDataArray = $this->buildComboBox( $parent_link, $entityChildren, $selectedId, $dataName);
				    if (count($childDataArray) > 0)
				    {
					    $dataArray = array_merge($dataArray, $childDataArray);
				    }
			    }
		    }

		    return $dataArray;
	    }

    }