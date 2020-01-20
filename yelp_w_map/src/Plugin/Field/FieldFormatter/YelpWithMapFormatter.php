<?php
/**
 * @file
 * Contains \Drupal\yelp_w_map\Plugin\Field\FieldFormatter\YelpWithMapFormatter.
 */
namespace Drupal\yelp_w_map\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Yelp with Map' formatter.
 *
 * @FieldFormatter(
 *   id = "yelp_w_map",
 *   label = @Translation("YelpWithMap"),
 *   field_types = {
 *     "string",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string_long",
 *   }
 * )
 */
class YelpWithMapFormatter extends FormatterBase {


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      "apikey" => "",
      "yelp_apikey" => "",
      "pic_width" => "",
      "map_width" => "",
      "map_height" => "",
    ] + parent::defaultSettings();
  }

    /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['embedded_label'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Embedded map') . '</h3>',
    ];

    $elements['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $this->getSetting('apikey'),
      '#description' => $this->t('Google Maps will not work without an API key. See the <a href="https://developers.google.com/maps/documentation/static-maps" target="_blank">Static Maps API page</a> to learn more and obtain a key.'),
    ];

    $elements['yelp_apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Yelp API key'),
      '#default_value' => $this->getSetting('yelp_apikey'),
      '#description' => $this->t('Yelp API will not work without an API key. See the <a href="https://yelp.com/fusion" target="_blank"> API page</a> to learn more and obtain a key.'),
    ];

    $elements['pic_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width of yelp review images'),
      '#default_value' => $this->getSetting('pic_width'),
      '#description' => $this->t('You can set sizes in px (ex: 600 for 600px). Note that static value only accept sizes in pixels. Digit only'),
      '#size' => 200,
    ];

    $elements['map_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width of yelp map'),
      '#default_value' => $this->getSetting('map_width'),
      '#description' => $this->t('You can set sizes in px (ex: 600 for 600px). If input zero, map will not display. Note that static value only accept sizes in pixels. Digit only'),
      '#size' => 200,
    ];

    $elements['map_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height of yelp map'),
      '#default_value' => $this->getSetting('map_height'),
      '#description' => $this->t('You can set sizes in px (ex: 600 for 600px). If input zero, map will not display. Note that static value only accept sizes in pixels. Digit only'),
      '#size' => 200,
    ];

    return $elements;
  }

    /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }




  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode=NULL) {
    global $base_path;

    $elements = array();
    foreach ($items as $delta => $item) {

      $settings = $this->getSettings();
      $apikey=$settings['apikey'];
      $pic_width=$settings['pic_width'];
      $map_width=$settings['map_width'];
      $map_height=$settings['map_height'];
      $yelp_apikey=$settings['yelp_apikey'];

      if (!is_numeric($pic_width)){
	      $pic_width=200;
      }
      if (!is_numeric($map_width)){
	      $map_width=0;
      }
      if (!is_numeric($map_height)){
	      $map_height=0;
      }
      if ($map_width==0 || $map_height==0 || strlen($apikey)==0){
	      $map_width=0;
	      $map_height=0;
      }

      $url_params=array();

      //Get the business information from Phone number.
      $phone_number=$item->value;
      $phone_number=$this->yelp_w_map_yelp_strip_phone_number($phone_number);

      if (strlen($phone_number) > 0){

	  $phone_number="+".$phone_number; //add plus to user API

          $url_params['phone']=$phone_number; //phone number
          $url="/v3/businesses/search/phone";
          $req_rev="";
          $reviews=array();

          $req=$this->yelp_w_map_yelp_request($yelp_apikey, $url, $url_params); //Call Yelp API to get Business information from Phone number

          $yelp_data=array(); //initialize data

          if (strlen($req) > 0){
              $data=json_decode($req);
	      if (isset($data->businesses)){
		  $business_id=$data->businesses[0]->id;
		  $alias=$data->businesses[0]->alias;
		  $name=$data->businesses[0]->name;
		  $img_url=$data->businesses[0]->image_url;
		  $yelp_url=$data->businesses[0]->url;
		  $review_count=$data->businesses[0]->review_count;
		  $rating=$data->businesses[0]->rating;
		  $coordinates=$data->businesses[0]->coordinates;
		  $location=$data->businesses[0]->location;
		  $category=$data->businesses[0]->categories;
		  $display_phone=$data->businesses[0]->display_phone;

		  $yelp_data['name']=$name;
		  $yelp_data['img_url']=$img_url;
		  $yelp_data['review_count']=$review_count;
		  $yelp_data['rating']=$rating;
		  $yelp_data['url']=$yelp_url;
		  $yelp_data['display_phone']=$display_phone;

		  //Location data for display google maps
		  $yelp_data['lat']=$coordinates->latitude;
		  $yelp_data['lng']=$coordinates->longitude;

		  $yelp_data['display_address']="";
		  for ($x=0; $x < count($location->display_address); $x++){
			  $yelp_data['display_address'].=$location->display_address[$x]." ";
		  }

		  //set Yelp Logo images file
		  $yelp_logo=$base_path.drupal_get_path('module', 'yelp_w_map').'/images/yelp-logo.png';
		  $yelp_data['img_rating']=$this->yelp_w_map_yelp_icon_star($rating);

		  if (strlen($business_id) > 0 && $review_count > 0){
      			$url_params=array();
			$url_params['locale']="en_US";
			$url="/v3/businesses/".$business_id."/reviews";

                        $req_rev=$this->yelp_w_map_yelp_request($yelp_apikey, $url, $url_params); //Call Yelp API to get the Reviews

			if (strlen($req_rev) > 0){
				$data_rev=json_decode($req_rev);
				if (isset($data_rev->reviews)){
					$reviews=$data_rev->reviews;
					for ($x=0; $x < count($reviews); $x++){
						$reviews[$x]->img_rating=$this->yelp_w_map_yelp_icon_star($reviews[$x]->rating); //Get Star icon image

						if (isset($reviews[$x]->user)){
							$rev_user=$reviews[$x]->user;
							if (isset($rev_user->profile_url)){
								$reviews[$x]->profile_url=$rev_user->profile_url;
							}
							if (isset($rev_user->image_url)){
								$reviews[$x]->profile_img_url=$rev_user->image_url;
							}
							$reviews[$x]->user_name=$rev_user->name;
							$reviews[$x]->profile_url=$rev_user->profile_url;
						}
					}
				}
			}
	          }
	      }
          }
      }

      $yelp_format['pic_width']=$pic_width;
      $yelp_format['map_width']=$map_width;
      $yelp_format['map_height']=$map_height;
      $yelp_format['gmap_apikey']=$apikey;

      $element[$delta]=[
        '#theme' => 'yelp_w_map_output',
        '#yelp_data' => $yelp_data,
        '#yelp_logo' => $yelp_logo,
        '#reviews' => $reviews,
        '#yelp_format' => $yelp_format,
      ];

    }
    return $element;
  }

  //phone number
  private function yelp_w_map_yelp_strip_phone_number($v){
	  $v=trim($v);
	  $v=preg_replace("/[^0-9]/", "", $v); //get only numbers

	  return $v;
  }

  //Set star image file
  private function yelp_w_map_yelp_icon_star($rating){
	  global $base_path;

	  $v=(int)($rating*10);

	  if ($v%10==0){
		  //full star images
		  $img_url=$base_path.drupal_get_path('module', 'yelp_w_map')."/images/regular_".$rating.".png";
	  }
	  else{
		  //half star images
		  $p=($v-($v%10))/10;
		  $img_url=$base_path.drupal_get_path('module', 'yelp_w_map')."/images/regular_".$p."_half.png";
	  }

	  return $img_url;
  }


/**
 * Makes a request to the Yelp API and returns the response
 *
 * @param    $host    The domain host of the API
 * @param    $path    The path of the API after the domain.
 * @param    $url_params    Array of query-string parameters.
 * @return   The JSON response from the request
 */
    private function yelp_w_map_yelp_request($apikey, $surl,  $url_params = array()) {
    // Send Yelp API Call
    $host="https://api.yelp.com"; //Yelp API end point
    $search_path=$surl;

    try {
        $curl = curl_init();
        if (FALSE === $curl)
            throw new Exception('Failed to initialize');
	$url = $host . $search_path;
	if (count($url_params) > 0){
            $url.= "?" . http_build_query($url_params);
	}

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,  // Capture response.
            CURLOPT_ENCODING => "",  // Accept gzip/deflate/whatever.
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $apikey,
                "cache-control: no-cache",
            ),
        ));
        $response = curl_exec($curl);

	//return null string if it occured error
	//
        if (FALSE === $response)
	    return ""; //return empty string
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (200 != $http_status)
	    return ""; //return empty string
        curl_close($curl);
    } catch(Exception $e) {
	    return ""; //return empty string
    }
    return $response;
    }

}
?>
