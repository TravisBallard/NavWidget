<?php

    class NavWidgetLink
    {
        private $ID;
        private $parent_ID;
        private $url;
        private $text;
        private $open_in_new_window;

        private $children = array();

        /**
        * magic
        *
        * @param mixed $url
        * @param mixed $text
        * @param mixed $parent_ID
        * @param mixed $open_in_new_window
        *
        * @return NavWidgetLink
        */
        public function __construct( $url, $text, $parent_ID = 0, $open_in_new_window = false )
        {
            $this->ID = uniqid( 'link_' );
            $this->set_url( $url );
            $this->set_text( $text );
            $this->set_parent_ID( $parent_ID );
            $this->set_open_in_new_window( $open_in_new_window );
        }

        /**
        * set the url
        *
        * @param mixed $url
        */
        public function set_url( $url ){
            $this->url = esc_url( $url );
        }

        /**
        * set the text
        *
        * @param mixed $text
        */
        public function set_text( $text ){
            $this->text = esc_attr( $text );
        }

        /**
        * set open in new window value to true or false
        *
        * @param mixed $open_in_new_window
        */
        public function set_open_in_new_window( $open_in_new_window ){
            $this->open_in_new_window = (bool)$open_in_new_window === true ? true : false;
        }

        /**
        * set parent link id
        *
        * @param mixed $id
        */
        public function set_parent_ID( $id ){
            $this->parent_ID = esc_attr( $id );
        }

        /**
        * get value of open in new window property
        *
        */
        public function open_in_new_window(){
            return (bool)$this->open_in_new_window;
        }

        /**
        * get link id
        *
        */
        public function get_id(){
            return $this->ID;
        }

        /**
        * get the url
        *
        */
        public function get_url(){
            return $this->url;
        }

        /**
        * get the link text
        *
        */
        public function get_text(){
            return $this->text;
        }

        /**
        * get the parent link ID
        *
        */
        public function get_parent_ID(){
            return $this->parent_ID;
        }

        /**
        * build the link so we dont have to do this for each one on output
        *
        */
        public function get_link()
        {
            $href = ! empty( $this->url ) ? sprintf( ' href="%s"', $this->get_url() ) : '';
            return apply_filters( 'nav_widget_link', sprintf( '<a class="nav-widget-link"%s>%s</a>', $href, $this->get_text() ) );
        }

        /**
        * add child link
        *
        * @param NavWidgetLink $link
        * @return void
        */
        public function add_child_link(NavWidgetLink $link)
        {
            $link->set_parent_ID($this->get_id());
            $this->children[ $link->get_id() ] = $link;
        }

        /**
        * check if there's children or not based on the size of the array
        *
        */
        public function has_children(){
            return count( $this->children ) == 0 ? false : true;
        }

        /**
        * get children
        *
        */
        public function get_children(){
            return $this->has_children() ? $this->children : array();
        }

		/**
		 * See if a NavWidgetLink is a child of this instance
		 * @param NavWidgetLink $link
		 * @return bool
		 */
		public function is_child(NavWidgetLink $link)
		{
			return in_array($link, $this->children);
		}

        /**
        * Set the child links for this link object
        *
        * @param array<NavWidgetLink> $children
        */
        public function set_children(array $children){
            $this->children = $children;
        }

        /**
        * delete a child link
        *
        * @param mixed $id
        */
        public function delete_child( $id )
        {
            if (array_key_exists( $id, $this->children ))
            {
                unset( $this->children[ $id ] );
                return true;
            }

            foreach ($this->children as $child)
            {
                if ($child->delete_child($id)){
                    return true;
                }
            }

            return false;
        }
    }