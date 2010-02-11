<?php
	/*
	 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
	 *    Copyright 2010 John Hobbs
	 *
	 *  Licensed under the Apache License, Version 2.0 (the "License");
	 *  you may not use this file except in compliance with the License.
	 *  You may obtain a copy of the License at
	 *
	 *      http://www.apache.org/licenses/LICENSE-2.0
	 *
	 *  Unless required by applicable law or agreed to in writing, software
	 *  distributed under the License is distributed on an "AS IS" BASIS,
	 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	 *  See the License for the specific language governing permissions and
	 *  limitations under the License.
	 *
	 */

	class TwitterfavoritesModel extends SourceModel {

		protected $_name 	= 'twitterfavorites_data';

		protected $_prefix = 'twitterfavorites';

		protected $_search  = 'content,title';

		protected $_update_tweet = "Favorited %d tweets on my lifestream %s";

		public function getServiceName() {
			return "Twitter Favorites";
		}

		public function isStoryElement() {
			return true;
		}

		public function getServiceURL() {
			return 'http://twitter.com/' . $this->getProperty( 'username' );
		}

		public function getServiceDescription() {
			return "Twitter is a social microblogging site where you can mark other peoples tweets as 'favorites'.";
		}

		public function getAccountName() {
			if( $name = $this->getProperty( 'username' ) ) {
				return $name;
			}
			else {
				return false;
			}
		}

		public function getTitle() {
			return $this->getServiceName();
		}

		public function importData() {
			$items = $this->updateData();
			$this->setImported( true );
			return $items;
		}

		public function updateData() {
			$url = 'http://twitter.com/favorites.xml?id=' . $this->getProperty( 'username' );

			$curl = curl_init();
			curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_HEADER, false );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_USERAGENT, 'Storytlr/1.0' );

			$response = curl_exec( $curl );
			$http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			curl_close( $curl );

			if ( $http_code != 200 ) {
				throw new Stuffpress_Exception( "Twitter returned http status $http_code for url: $url", $http_code );
			}

			if ( ! ( $items = simplexml_load_string( $response ) ) ) {
				throw new Stuffpress_Exception( "Twitter did not return any result", 0 );
			}

			if ( count( $items->status ) == 0 ) { return; }

			$items = $this->processItems( $items->status );
			$this->markUpdated();
			return $items;
		}

		private function processItems( $statuses ) {
			$result = array();
			foreach ( $statuses as $status ) {
				$data = array();
				$data['tweet_id'] = $status->id;
				$data['title'] = $this->getProperty( 'username' ) . ' favorited a tweet by ' . $status->user->screen_name;
				$data['published'] = strtotime( $status->created_at );
				$data['content'] = $status->text;
				$data['link'] = 'http://twitter.com/' . $status->user->screen_name . '/status/' . $status->id;
				$data['author'] = $status->user->screen_name;
				$tags = array();
				preg_match_all( "/#(\w+)/", $status, $tags );
				$id = $this->addItem( $data, $data['published'], SourceItem::LINK_TYPE, $tags[1], false, false, $data['title'] );
				if ( $id ) $result[] = $id;
			}
			return $result;
		}

		public function getConfigForm( $populate=false ) {
			$form = new Stuffpress_Form();

			// Add the username element
			$element = $form->createElement( 'text', 'username', array( 'label' => 'Username', 'decorators' => $form->elementDecorators ) );
			$element->setRequired( true );
			$form->addElement( $element );

			// Populate
			if( $populate ) {
				$values  = $this->getProperties();
				$form->populate( $values );
			}

			return $form;
		}

		public function processConfigForm($form) {
			$values = $form->getValues();
			$update	= false;

			if( $values['username'] != $this->getProperty( 'username' ) ) {
				$this->_properties->setProperty( 'username',   $values['username'] );
				$update = true;
			}

			return $update;
		}
	}
