<?php
	/*
		Class: BigTree\Instagram\API
			Instagram API class that implements most API calls (media posting excluded).
	*/

	namespace BigTree\Instagram;

	use BigTree\OAuth;
	use stdClass;

	class API extends OAuth {

		public $AuthorizeURL = "https://api.instagram.com/oauth/authorize/";
		public $EndpointURL = "https://api.instagram.com/v1/";
		public $OAuthVersion = "1.0";
		public $RequestType = "custom";
		public $Scope = "basic comments relationships likes";
		public $TokenURL = "https://api.instagram.com/oauth/access_token";

		/*
			Constructor:
				Sets up the Instagram API connections.

			Parameters:
				cache - Whether to use cached information (15 minute cache, defaults to true)
		*/

		function __construct($cache = true) {
			parent::__construct("bigtree-internal-instagram-api","Instagram API","org.bigtreecms.api.instagram",$cache);

			// Set OAuth Return URL
			$this->ReturnURL = ADMIN_ROOT."developer/services/instagram/return/";

			// Just send the request with the secret.
			$this->RequestParameters = array();
			$this->RequestParameters["access_token"] = &$this->Settings["token"];
		}

		/*
			Function: callUncached
				Piggybacks on the base call to provide error checking for Instagram.
		*/

		function callUncached($endpoint = "",$params = array(),$method = "GET",$headers = array()) {
			$response = parent::callUncached($endpoint,$params,$method,$headers);

			if (isset($response->meta->error_message)) {
				$this->Errors[] = $response->meta->error_message;
				return false;
			}

			return $response;
		}

		/*
			Function: comment
				Leaves a comment on a media post by the authenticated user.
				This method requires special access permissions for your Instagram application.
				Please email apidevelopers@instagram.com for access.

			Parameters:
				id - The media ID to comment on.
				comment - The text to leave as a comment.

			Returns:
				true if successful
		*/

		function comment($id,$comment) {
			$response = $this->call("media/$id/comments",array("text" => $comment),"POST");

			if ($response->meta->code == 200) {
				return true;
			}

			return false;
		}

		/*
			Function: deleteComment
				Leaves a comment on a media post by the authenticated user.

			Parameters:
				id - The media ID the comment was left on.
				comment - The comment ID.

			Returns:
				true if successful
		*/

		function deleteComment($id,$comment) {
			$response = $this->call("media/$id/comments/$comment",array(),"DELETE");

			if ($response->meta->code == 200) {
				return true;
			}

			return false;
		}

		/*
			Function: getComments
				Returns a list of comments for a given media ID.

			Parameters:
				id - The media ID to retrieve comments for.

			Returns:
				An array of BigTree\Instagram\Comment objects.
		*/

		function getComments($id) {
			$response = $this->call("media/$id/comments");
			
			if (!isset($response->data)) {
				return false;
			}
			
			$comments = array();
			foreach ($response->data as $comment) {
				$comments[] = new Comment($comment,$id,$this);
			}
			
			return $comments;
		}

		/*
			Function: getFeed
				Returns the authenticated user's feed.

			Parameters:
				count - The number of media results to return (defaults to 10)
				params - Additional parameters to pass to the users/self/feed API call

			Returns:
				A BigTree\Instagram\ResultSet of BigTree\Instagram\Media objects.

			See Also:
				http://instagram.com/developer/endpoints/users/
		*/

		function getFeed($count = 10,$params = array()) {
			$response = $this->call("users/self/feed",array_merge($params,array("count" => $count)));
			
			if (!isset($response->data)) {
				return false;
			}
			
			$results = array();
			foreach ($response->data as $media) {
				$results[] = new Media($media,$this);
			}
			
			return new ResultSet($this,"getFeed",array($count,array_merge($params,array("max_id" => end($results)->ID))),$results);
		}

		/*
			Function: getFriends
				Returns a list of people the given user ID follows

			Parameters:
				id - The user ID to retrieve the friends of

			Returns:
				An array of BigTree\Instagram\User objects
		*/

		function getFriends($id) {
			$response = $this->call("users/$id/follows");
			
			if (!isset($response->data)) {
				return false;
			}
			
			$results = array();
			foreach ($response->data as $user) {
				$results[] = new User($user,$this);
			}
			
			return $results;
		}

		/*
			Function: getFollowers
				Returns a list of people the given user ID is followed by

			Parameters:
				id - The user ID to retrieve the followers of

			Returns:
				An array of BigTree\Instagram\User objects
		*/

		function getFollowers($id) {
			$response = $this->call("users/$id/followed-by");
			
			if (!isset($response->data)) {
				return false;
			}
			
			$results = array();
			foreach ($response->data as $user) {
				$results[] = new User($user,$this);
			}
			
			return $results;
		}

		/*
			Function: getFollowRequests
				Returns a list of people that are awaiting permission to follow the authenticated user

			Returns:
				An array of BigTree\Instagram\User objects
		*/

		function getFollowRequests() {
			$response = $this->call("users/self/requested-by");
			
			if (!isset($response->data)) {
				return false;
			}
			
			$results = array();
			foreach ($response->data as $user) {
				$results[] = new User($user,$this);
			}
			
			return $results;
		}

		/*
			Function: getLikedMedia
				Returns a list of media the authenticated user has liked

			Parameters:
				count - The number of media results to return (defaults to 10)
				params - Additional parameters to pass to the users/self/media/liked API call

			Returns:
				A BigTree\Instagram\ResultSet of BigTree\Instagram\Media objects.

			See Also:
				http://instagram.com/developer/endpoints/users/
		*/

		function getLikedMedia($count = 10,$params = array()) {
			$response = $this->call("users/self/media/liked",array_merge($params,array("count" => $count)));

			if (!isset($response->data)) {
				return false;
			}

			$results = array();
			foreach ($response->data as $media) {
				$results[] = new Media($media,$this);
			}

			return new ResultSet($this,"getLikedMedia",array($count,array_merge($params,array("max_like_id" => end($results)->ID))),$results);
		}

		/*
			Function: getLikes
				Returns a list of users that like a given media ID.

			Parameters:
				id - The media ID to get likes for

			Returns:
				An array of BigTree\Instagram\User objects.
		*/

		function getLikes($id) {
			$response = $this->call("media/$id/likes");

			if (!isset($response->data)) {
				return false;
			}

			$users = array();
			foreach ($response->data as $user) {
				$users[] = new User($user,$this);
			}

			return $users;
		}

		/*
			Function: getLocation
				Returns location information for a given ID.

			Parameters:
				id - The location ID

			Returns:
				A BigTree\Instagram\Location object.
		*/

		function getLocation($id) {
			$response = $this->call("locations/$id");

			if (!isset($response->data)) {
				return false;
			}

			return new Location($response->data,$this);
		}

		/*
			Function: getLocationByFoursquareID
				Returns location information for a given Foursquare API v2 ID.

			Parameters:
				id - The Foursquare API ID.

			Returns:
				A BigTree\Instagram\Location object.
		*/

		function getLocationByFoursquareID($id) {
			$response = $this->searchLocations(false,false,false,$id);

			if (!$response) {
				return false;
			}

			return $response[0];
		}

		/*
			Function: getLocationByLegacyFoursquareID
				Returns location information for a given Foursquare API v1 ID.

			Parameters:
				id - The Foursquare API ID.

			Returns:
				A BigTree\Instagram\Location object.
		*/

		function getLocationByLegacyFoursquareID($id) {
			$response = $this->searchLocations(false,false,false,false,$id);

			if (!$response) {
				return false;
			}

			return $response[0];
		}

		/*
			Function: getLocationMedia
				Returns recent media from a given location

			Parameters:
				id - The location ID to pull media for
				params - Additional parameters to pass to the locations/{id}/media/recent API call

			Returns:
				A BigTree\Instagram\ResultSet of BigTree\Instagram\Media objects.

			See Also:
				http://instagram.com/developer/endpoints/locations/
		*/

		function getLocationMedia($id,$params = array()) {
			$response = $this->call("locations/$id/media/recent",$params);

			if (!isset($response->data)) {
				return false;
			}

			$results = array();
			foreach ($response->data as $media) {
				$results[] = new Media($media,$this);
			}

			return new ResultSet($this,"getLocationMedia",array($id,array("max_id" => end($results)->ID)),$results);
		}

		/*
			Function: getMedia
				Gets information about a given media ID

			Parameters:
				id - The media ID
				shortcode - The media shortcode (from instagram.com shortlink URL, optional & replaces ID)

			Returns:
				A BigTree\Instagram\Media object.
		*/

		function getMedia($id, $shortcode = false) {
			if ($shortcode) {
				$response = $this->call("media/shortcode/$id");
			} else {
				$response = $this->call("media/$id");
			}

			if (!isset($response->data)) {
				return false;
			}

			return new Media($response->data,$this);
		}

		/*
			Function: getRelationship
				Returns the relationship of the given user to the authenticated user

			Parameters:
				id - The user ID to check the relationship of

			Returns:
				An object containg an "Incoming" key (whether they follow you, have requested to follow you, or nothing) and "Outgoing" key (whether you follow them, block them, etc)
		*/

		function getRelationship($id) {
			$response = $this->call("users/$id/relationship");

			if (!isset($response->data)) {
				return false;
			}

			$obj = new stdClass;
			$obj->Incoming = $response->data->incoming_status;
			$obj->Outgoing = $response->data->outgoing_status;

			return $obj;
		}

		/*
			Function: getTaggedMedia
				Returns recent photos that contain a given tag.

			Parameters:
				tag - The tag to search for
				params - Additional parameters to pass to the tags/{tag}/media/recent API call

			Returns:
				A BigTree\Instagram\ResultSet of BigTree\Instagram\Media objects.

			See Also:
				http://instagram.com/developer/endpoints/tags/
		*/

		function getTaggedMedia($tag,$params = array()) {
			$tag = (substr($tag,0,1) == "#") ? substr($tag,1) : $tag;
			$response = $this->call("tags/$tag/media/recent",$params);

			if (!isset($response->data)) {
				return false;
			}

			$results = array();
			foreach ($response->data as $media) {
				$results[] = new Media($media,$this);
			}

			return new ResultSet($this,"getTaggedMedia",array($tag,array("min_id" => end($results)->ID)),$results);
		}

		/*
			Function: getUser
				Returns information about a given user ID.

			Parameters:
				id - The user ID to look up

			Returns:
				A BigTree\Instagram\User object.
		*/

		function getUser($id) {
			$response = $this->call("users/$id");

			if (!isset($response->data)) {
				return false;
			}

			return new User($response->data,$this);
		}

		/*
			Function: getUserMedia
				Returns recent media from a given user ID.

			Parameters:
				id - The user ID to return media for.
				count - The number of media results to return (defaults to 10).
				params - Additional parameters to pass to the users/{id}/media/recent API call.

			Returns:
				A BigTree\Instagram\ResultSet of BigTree\Instagram\Media objects.

			See Also:
				http://instagram.com/developer/endpoints/users/
		*/

		function getUserMedia($id,$count = 10,$params = array()) {
			$response = $this->call("users/$id/media/recent",array_merge($params,array("count" => $count)));

			if (!isset($response->data)) {
				return false;
			}

			$results = array();
			foreach ($response->data as $media) {
				$results[] = new Media($media,$this);
			}

			return new ResultSet($this,"getUserMedia",array($id,$count,array_merge($params,array("max_id" => end($results)->ID))),$results);
		}

		/*
			Function: like
				Sets a like on the given media by the authenticated user.

			Parameters:
				id - The media ID to like

			Returns:
				true if successful
		*/

		function like($id) {
			$response = $this->call("media/$id/likes",array(),"POST");

			if ($response->meta->code == 200) {
				return true;
			}

			return false;
		}

		/*
			Function: popularMedia
				Returns a list of popular media.

			Returns:
				An array of BigTree\Instagram\Media objects.
		*/

		function popularMedia() {
			$response = $this->call("media/popular");

			if (!isset($response->data)) {
				return false;
			}

			$results = array();
			foreach ($response->data as $media) {
				$results[] = new Media($media,$this);
			}

			return $results;
		}

		/*
			Function: searchLocations
				Returns locations that match the search location or Foursquare ID

			Parameters:
				latitude - Latitude (required if not searching by Foursquare ID)
				longitude - Longitude (required if not searching by Foursquare ID)
				distance - Numeric value in meters to search from the lat/lon location (defaults to 1000)
				foursquare_id - Foursquare API v2 ID to search by (ignores lat/lon)
				legacy_foursquare_id - Legacy Foursquare API v1 ID to search by (ignores lat/lon and API v2 ID)

			Returns:
				An array of BigTree\Instagram\Location objects
		*/

		function searchLocations($latitude = false,$longitude = false,$distance = 1000,$foursquare_id = false,$legacy_foursquare_id = false) {
			if ($legacy_foursquare_id) {
				$response = $this->call("locations/search",array("foursquare_id" => $legacy_foursquare_id));
			} elseif ($foursquare_id) {
				$response = $this->call("locations/search",array("foursquare_v2_id" => $foursquare_id));
			} else {
				$response = $this->call("locations/search",array("lat" => $latitude,"lng" => $longitude,"distance" => intval($distance)));
			}

			if (!isset($response->data)) {
				return false;
			}

			$locations = array();
			foreach ($response->data as $location) {
				$locations[] = new Location($location,$this);
			}

			return $locations;
		}

		/*
			Function: searchMedia
				Search for media taken in a given area.

			Parameters:
				latitude - Latitude
				longitude - Longitude
				distance - Distance (in meters) to search (default is 1000, max is 5000)
				params - Additional parameters to pass to the media/search API call

			Returns:
				A BigTree\Instagram\ResultSet of BigTree\Instagram\Media objects.

			See Also:
				http://instagram.com/developer/endpoints/media/
		*/

		function searchMedia($latitude,$longitude,$distance = 1000,$params = array()) {
			$response = $this->call("media/search",array_merge($params,array("lat" => $latitude,"lng" => $longitude,"distance" => intval($distance))));

			if (!isset($response->data)) {
				return false;
			}

			$results = array();
			foreach ($response->data as $media) {
				$results[] = new Media($media,$this);
			}

			return new ResultSet($this,"searchMedia",array($latitude,$longitude,$distance,array_merge($params,array("max_timestamp" => strtotime(end($results)->Timestamp)))),$results);
		}

		/*
			Function: searchTags
				Returns tags that match the search query.
				Exact match is the first result followed by most popular.
				If the exact match is popular enough, it is the only result.

			Parameters:
				tag - Tag to search for

			Returns:
				An array of BigTree\Instagram\Tag objects.
		*/

		function searchTags($tag) {
			$response = $this->call("tags/search",array("q" => (substr($tag,0,1) == "#") ? substr($tag,1) : $tag));

			if (!isset($response->data)) {
				return false;
			}

			$tags = array();
			foreach ($response->data as $tag) {
				$tags[] = new Tag($tag,$this);
			}

			return $tags;
		}

		/*
			Function: searchUsers
				Returns users that match the search query.

			Parameters:
				query - String to search for.
				count - Number of results to return (defaults to 10)

			Returns:
				An array of BigTree\Instagram\User objects.
		*/

		function searchUsers($query,$count = 10) {
			$response = $this->call("users/search",array("q" => $query,"count" => $count));

			if (!isset($response->data)) {
				return false;
			}

			$users = array();
			foreach ($response->data as $user) {
				$users[] = new User($user,$this);
			}

			return $users;
		}

		/*
			Function: setRelationship
				Modifies the authenticated user's relationship with the given user.

			Parameters:
				id - The user ID to set relationship status with
				action - "follow", "unfollow", "block", "unblock", "approve", or "deny"

			Returns:
				true if successful.
		*/

		function setRelationship($id,$action) {
			$response = $this->call("users/$id/relationship",array("action" => $action),"POST");

			if (!isset($response->data)) {
				return false;
			}

			return true;
		}

		/*
			Function: unlike
				Removes a like on the given media set by the authenticated user.

			Parameters:
				id - The media ID to like

			Returns:
				true if successful
		*/

		function unlike($id) {
			$response = $this->call("media/$id/likes",array(),"DELETE");

			if ($response->meta->code == 200) {
				return true;
			}

			return false;
		}

	}