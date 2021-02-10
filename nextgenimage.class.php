<?php
class PerchFieldType_nextgenimage extends PerchFieldType
{
    public static $file_paths = [];
    public $wrap_class = 'annotated ';
    protected $accept_types = 'webimage';

    public function render_inputs($details=array())
    {
        $Perch = Perch::fetch();
        $Assets = new PerchAssets_Assets;


        if ($this->Tag->bucket()) {
            $Users       = new PerchUsers;
            $CurrentUser = $Users->get_current_user();
            $buckets = explode(' ', $this->Tag->bucket());
            $buckets = $Assets->hydrate_bucket_list($buckets, $CurrentUser);
            if (count($buckets)) {
                $bucket  = $buckets[0];
                $Bucket = PerchResourceBuckets::get($bucket);    
            } else {
                $Bucket = PerchResourceBuckets::get('default');
            }
            
        } else {
            $Bucket = PerchResourceBuckets::get($this->Tag->bucket());
        }
        

        

        

        $PerchImage = new PerchImage;
        $s = $this->Form->image($this->Tag->input_id(), $this->Tag->title());
        $s .= $this->Form->hidden($this->Tag->input_id().'_field', '1');

        $assetID     = false;
        $Asset       = false;
        $asset_field = $this->Tag->input_id().'_assetID';

        $badge_rendered = false;

        if (isset($details[$this->Tag->input_id()]['assetID'])) {
            $assetID = $details[$this->Tag->input_id()]['assetID'];
        }

        // Alt tag?
        $alt_text = '';
        if (isset($details[$this->Tag->input_id()]['alt'])) {
            $alt_text = $details[$this->Tag->input_id()]['alt'];
        }

        if ($assetID) {
            $Asset = $Assets->find($assetID);       
        }

        $s .= $this->Form->hidden($asset_field, $assetID);

        $Bucket->initialise();

        if (!$Bucket->ready_to_write()) {
            $s .= $this->Form->hint(PerchLang::get('Your resources folder is not writable. Make this folder (') . PerchUtil::html($Bucket->get_web_path()) . PerchLang::get(') writable to upload images.'), 'error');
        }

        $type = 'img';
        if ($this->Tag->file_type()) $type = $this->Tag->file_type();

        
        $Settings = PerchSettings::fetch();
        $permissive = '';
        if (!(int)$Settings->get('assets_restrict_buckets')->val()) {
            $permissive = ' data-permissive-bucketing="true"';
        } 


        $add_cta = ' <div class="asset-add ft-choose-asset'.($this->Tag->disable_asset_panel() ? ' assets-disabled' : '').'" data-type="'.$type.'" data-field="'.$asset_field.'" data-input="'.$this->Tag->input_id().'" data-app="'.$this->app_id.'" data-app-uid="'.$this->unique_id.'" data-bucket="'.PerchUtil::html($Bucket->get_name(), true).'"'.$permissive.'></div>';


        if (isset($details[$this->Tag->input_id()]) && $details[$this->Tag->input_id()]!='') {
            $json = $details[$this->Tag->input_id()];
            //PerchUtil::debug($json);

            if (isset($json['bucket'])) {
                $Bucket = PerchResourceBuckets::get($json['bucket']);
            }

            if (isset($json['mime']) && strpos($json['mime'],'svg')!==false) {
                if ($Asset) {
                    $json = array_merge($json, $Asset->get_fieldtype_profile());
                    //PerchUtil::debug($json);
                }
            }

            

            if (isset($json['sizes']['thumb'])) {

                $image_src  = $json['sizes']['thumb']['path'];
                $image_w    = $json['sizes']['thumb']['w'];
                $image_h    = $json['sizes']['thumb']['h'];

            } else {

                // For items imported from previous version
                if (is_string($json)) {
                    $image_src = str_replace(PERCH_RESPATH, '', $PerchImage->get_resized_filename($json, 150, 150, 'thumb'));
                    $image_w   = '150';
                    $image_h   = '150';
                }else{
                    $image_src = false;
                    $image_w   = '';
                    $image_h   = '';
                }
                

            }

            $image_path = false;

            if ($image_src) {
                
                $image_path = PerchUtil::file_path($Bucket->get_file_path().'/'.$image_src);

                $s .= '<div class="asset-badge" data-for="'.$asset_field.'">
                        <div class="asset-badge-inner">';

                $variant_key = 'w'.$this->Tag->width().'h'.$this->Tag->height().'c'.($this->Tag->crop() ? '1' : '0').($this->Tag->density() ? '@'.$this->Tag->density().'x': '');

                $variant = (isset($json['sizes'][$variant_key]) ? $json['sizes'][$variant_key] : false);

                if (!$variant) {
                    $variant = $json;
                }


                $s .= '<div class="asset-badge-thumb">';
                    $s .= '<img src="'.PerchUtil::html($Bucket->get_web_path().'/'.$image_src).'" width="'.$image_w.'" height="'.$image_h.'" alt="Preview">';

                    // Remove
                    $s .= '<div class="asset-badge-remove">';
                    $s .= '<span class="asset-ban hidden">'.PerchUI::icon('assets/ban-alt', 64).'</span>';
                    $s .= '<span class="asset-badge-remove-fields">';
                    $s .= $this->Form->label($this->Tag->input_id().'_remove', PerchLang::get('Remove'), 'inline'). ' ';
                    $s .= $this->Form->checkbox($this->Tag->input_id().'_remove', '1', 0);
                    $s .= '</span><a href="#" class="asset-badge-remove-action" data-checkbox="'.$this->Tag->input_id().'_remove'.'">'.PerchUI::icon('core/cancel', 24, PerchLang::get('Remove')).'</a>';
                    $s .= '</div>';
                    // End remove

                $s .= '</div>'; // .asset-badge-thumb

                $s .= '<div class="asset-badge-meta">';

                if (!$this->Tag->is_set('app_mode')) {

                    if ($variant) {
                        //PerchUtil::debug($variant, 'notice');

                        $s .= '<h3 class="title">';
                        if ($Asset) {
                            $s .= PerchUtil::html($Asset->title());
                        } else {
                            $s .= PerchUtil::html((isset($json['title']) ? $json['title'] : $variant['path']));
                        }
                        $s .= '</h3>';

                        $s .= '<ul class="meta">';                        
                    
                        if ($Asset) {
                            $s .= '<li>'.PerchUI::icon('assets/o-photo', 12).' ';
                            $s .= $Asset->display_mime().'</li>';
                        } else {
                            if (isset($variant['mime']) && $variant['mime']!='') {
                                $s .= '<li>'.PerchUI::icon('assets/o-photo', 12).' ';
                                $s .= ucfirst(str_replace('/', ' / ', $variant['mime'])).'</li>';
                            }    
                        }

                        
                        

                        $size     = floatval($variant['size']);
                        if ($size < 1048576) {
                            $size = round($size/1024, 0).'<span class="unit">KB</span>';
                        }else{
                            $size = round($size/1024/1024, 0).'<span class="unit">MB</span>';
                        }
                        if (isset($variant['w']) && isset($variant['h'])) {
                            $s .= '<li>'.PerchUI::icon('assets/o-crop', 12).' ';
                            $s .= ''.$variant['w'].' x '.$variant['h'].'<span class="unit">px</span> @ ';    
                        } else {
                            $s .= '<li>'.PerchUI::icon('assets/o-weight-scale', 12).' ';
                        }
                        $s .= $size.'</li>';

                        $s .= '</ul>';

                        $s .= $add_cta;
                    } else {
                        PerchUtil::debug('no variant');
                    }

                } else {

                    if (!$Asset) $Asset = $Assets->find($assetID);

                    if ($Asset) {
                        $s .= '<ul class="meta">';
                        $s .= '<li><a href="'.$Asset->web_path().'">'.PerchLang::get('Download original file').'</a></li>';
                        $s .= '</ul>';
                    }

                } // app_mode

                $s .= '</div>'; // .asset-badge-meta
                $s .= '</div>'; // .asset-badge-inner

                $s .= '<br><p>Alt attribute</p>';
                $s .= $this->Form->text($this->Tag->input_id().'_alt', $alt_text) ; // alt attr input tag
                
                
                $s .= '</div>'; // .asset-badge

                $badge_rendered = true;

            }

        }

        if (!$badge_rendered) {
            $s .= '<div class="asset-badge" data-for="'.$asset_field.'">
                        <div class="asset-badge-inner">
                            <div class="asset-badge-thumb thumbless">';
                        $s .= PerchUI::icon('assets/upload', 64, PerchLang::get('Upload'));
                    $s .= '</div>';
                    $s .= '<div class="asset-badge-meta">';
                        $s .= $add_cta;
                    $s .= '</div>';
                $s .= '</div>';
                $s .= '<br><p>Alt attribute</p>';
                $s .= $this->Form->text($this->Tag->input_id().'_alt', $alt_text) ; // alt attr input tag

            $s .= '</div>';
        }


        if (isset($image_path) && !empty($image_path)) $s .= $this->Form->hidden($this->Tag->input_id().'_populated', '1');

        return $s;
    }

    public function get_raw($post=false, $Item=false)
    {
        $store                 = array();
        
        $Perch                 = Perch::fetch();
        $Bucket                = PerchResourceBuckets::get($this->Tag->bucket());
        
        $Bucket->initialise();

        $image_folder_writable = $Bucket->ready_to_write();
        
        $item_id               = $this->Tag->input_id();
        $asset_reference_used  = false;
        
        $target                = false;
        $filesize              = false;
        
        $Assets                = new PerchAssets_Assets;
        $AssetMeta             = false;


        // Asset ID?
        if (isset($post[$this->Tag->id().'_assetID']) && $post[$this->Tag->id().'_assetID']!='') {
            $new_assetID = $post[$this->Tag->id().'_assetID'];

            $Asset = $Assets->find($new_assetID);

            if (is_object($Asset)) {
                $target   = $Asset->file_path();
                $filename = $Asset->resourceFile();

                $store['assetID']  = $Asset->id();
                $store['title']    = $Asset->resourceTitle();
                $store['_default'] = $Asset->web_path();
                $store['bucket']   = $Asset->resourceBucket();

                if ($store['bucket']!=$Bucket->get_name()) {
                    $Bucket = PerchResourceBuckets::get($store['bucket']);
                }

                $asset_reference_used = true;
            }
        }

        // Alt tag?
        if (isset($post[$this->Tag->id().'_alt']) && $post[$this->Tag->id().'_alt']!='') {
            $store['alt'] = $post[$this->Tag->id().'_alt'];
        }

        if ($image_folder_writable && isset($_FILES[$item_id]) && (int) $_FILES[$item_id]['size'] > 0) {

            // If we haven't already got this file
            if (!isset(self::$file_paths[$this->Tag->id()])) {

                // Verify the file type / size / name
                if ($this->_file_is_acceptable($_FILES[$item_id])) {

                    // We do this before writing to the bucket, as it performs better for remote buckets.
                    $AssetMeta = $Assets->get_meta_data($_FILES[$item_id]['tmp_name'], $_FILES[$item_id]['name']);

                    // If it's an image, fix the orientation if we can
                    if ($this->Tag->type()=='image') {
                        $PerchImage = new PerchImage;
                        $PerchImage->orientate_image($_FILES[$item_id]['tmp_name']);
                    }

                    $result   = $Bucket->write_file($_FILES[$item_id]['tmp_name'], $_FILES[$item_id]['name']);

                    $target   = $result['path'];
                    $filename = $result['name'];
                    $filesize = (int)$_FILES[$item_id]['size'];

                    $store['_default'] = rtrim($Bucket->get_web_path(), '/').'/'.$filename;

                    // fire events
                    if ($this->Tag->type()=='image') {
                        $PerchImage = new PerchImage;
                        $profile = $PerchImage->get_resize_profile($target);
                        $profile['original'] = true;
                        $Perch->event('assets.upload_image', new PerchAssetFile($profile));
                    }

                }else{
                    $target = false;
                }


                
            }
        }

        if ($target && $filename && is_file($target)) {

            self::$file_paths[$this->Tag->id()] = $target;

            $store['path']   = $filename;
            $store['size']   = $filesize ?: filesize($target);
            $store['bucket'] = $Bucket->get_name();

            // Is this an SVG?
            $svg = false;

            $size = getimagesize($target);
            if (PerchUtil::count($size)) {
                $store['w'] = $size[0];
                $store['h'] = $size[1];
                if (isset($size['mime'])) $store['mime'] = $size['mime'];
            }else{
                $PerchImage = new PerchImage;

                if ($PerchImage->is_webp($target)) {

                    $store['mime'] = 'image/webp';

                }elseif ($PerchImage->is_svg($target)) {
                    $svg = true;
                    $size = $PerchImage->get_svg_size($target);
                    if (PerchUtil::count($size)) {
                        $store['w'] = $size['w'];
                        $store['h'] = $size['h'];
                        if (isset($size['mime'])) $store['mime'] = $size['mime'];
                    }
                }else{
                    // It's not an image (according to getimagesize) and not an SVG.
                    if ($this->Tag->detect_type()) {
                        // if we have permission to guess, our guess is that it's a file.
                        PerchUtil::debug('Guessing file', 'error');
                        $this->Tag->set('type', 'file');
                    }

                    $store['mime'] = PerchUtil::get_mime_type($target);
                }
            }

            

            // thumbnail
            if ($this->Tag->type()=='image' || $this->Tag->type()=='nextgenimage') {

                $PerchImage = new PerchImage;
                $PerchImage->set_density(2);

                $result = false;

                if ($asset_reference_used) {
                    $result = $Assets->get_resize_profile($store['assetID'], 150, 150, false, 'thumb', $PerchImage->get_density());
                }

                if (!$result) $result = $PerchImage->resize_image($target, 150, 150, false, 'thumb');
                if (is_array($result)) {
                    //PerchUtil::debug($result, 'notice');
                    if (!isset($store['sizes'])) $store['sizes'] = array();

                    $variant_key = 'thumb';
                    $tmp = array();
                    $tmp['w']        = $result['w'];
                    $tmp['h']        = $result['h'];
                    $tmp['target_w'] = 150;
                    $tmp['target_h'] = 150;
                    $tmp['density']  = 2;
                    $tmp['path']     = $result['file_name'];
                    $tmp['size']     = filesize($result['file_path']);
                    $tmp['mime']     = (isset($result['mime']) ? $result['mime'] : $store['mime']);

                    if (is_array($result) && isset($result['_resourceID'])) {
                        $tmp['assetID'] = $result['_resourceID'];
                    }

                    $store['sizes'][$variant_key] = $tmp;
                }
                unset($result);
                unset($PerchImage);
            }
            if ($this->Tag->type()=='file') {
                $PerchImage = new PerchImage;
                $PerchImage->set_density(2);

                $result = $PerchImage->thumbnail_file($target, 150, 150, false);
                if (is_array($result)) {
                    if (!isset($store['sizes'])) $store['sizes'] = array();

                    $variant_key = 'thumb';
                    $tmp = array();
                    $tmp['w']        = $result['w'];
                    $tmp['h']        = $result['h'];
                    $tmp['target_w'] = 150;
                    $tmp['target_h'] = 150;
                    $tmp['density']  = 2;
                    $tmp['path']     = $result['file_name'];
                    $tmp['size']     = filesize($result['file_path']);
                    $tmp['mime']     = (isset($result['mime']) ? $result['mime'] : '');

                    if (is_array($result) && isset($result['_resourceID'])) {
                        $tmp['assetID'] = $result['_resourceID'];
                    }

                    $store['sizes'][$variant_key] = $tmp;
                }
                unset($result);
                unset($PerchImage);
            }


        }

        
        // If webp is needed
        if ($target && !is_file($target . '.webp') && !$svg){
            $this->to_webp_with_gd($target, $target . '.webp');
        }

        // Loop through all tags with this ID, get their dimensions and resize the images.
        $all_tags = $this->get_sibling_tags();

        if (PerchUtil::count($all_tags)) {
            foreach($all_tags as $Tag) {
                if ($Tag->id()==$this->Tag->id()) {
                    // This is either this tag, or another tag in the template with the same ID.

                    if (($Tag->type()=='image' || $Tag->type()=='nextgenimage') && ($Tag->width() || $Tag->height()) && isset(self::$file_paths[$Tag->id()])) {

                        $variant_key = 'w'.$Tag->width().'h'.$Tag->height().'c'.($Tag->crop() ? '1' : '0').($Tag->density() ? '@'.$Tag->density().'x': '');

                        if (!isset($store['sizes'][$variant_key])) {

                            $PerchImage = new PerchImage;
                            if ($Tag->quality()) $PerchImage->set_quality($Tag->quality());
                            if ($Tag->is_set('sharpen')) $PerchImage->set_sharpening($Tag->sharpen());
                            if ($Tag->density()) $PerchImage->set_density($Tag->density());

                            $result = false;

                            if ($asset_reference_used) {
                                $result = $Assets->get_resize_profile($store['assetID'], $Tag->width(), $Tag->height(), $Tag->crop(), false, $PerchImage->get_density());
                            }

                            if (!$result || !file_exists($result['file_path'])) {
                                $result = $PerchImage->resize_image(self::$file_paths[$Tag->id()], $Tag->width(), $Tag->height(), $Tag->crop());
                            }
                            
                            // make webp
                            if ($result && !file_exists($result['file_path'] . '.webp')) {
                                $this->to_webp_with_gd($result['file_path'], $result['file_path'] . '.webp'); 
                            }

                            if (is_array($result)) {
                                if (!isset($store['sizes'])) $store['sizes'] = array();

                                $tmp             = array();
                                $tmp['w']        = $result['w'];
                                $tmp['h']        = $result['h'];
                                $tmp['target_w'] = $Tag->width();
                                $tmp['target_h'] = $Tag->height();
                                $tmp['crop']     = $Tag->crop();
                                $tmp['density']  = ($Tag->density() ? $Tag->density() : '1');
                                $tmp['path']     = $result['file_name'];
                                $tmp['size']     = filesize($result['file_path']);
                                $tmp['mime']     = (isset($result['mime']) ? $result['mime'] : '');

                                if ($result && isset($result['_resourceID'])) {
                                    $tmp['assetID'] = $result['_resourceID'];
                                }

                                $store['sizes'][$variant_key] = $tmp;

                                unset($tmp);
                            }

                            unset($result);
                            unset($PerchImage);
                        }
                    }
                }
            }
        }


        if (isset($_POST[$item_id.'_remove'])) {
            $store = array();
        }

        // If a file isn't uploaded...
        if (!$asset_reference_used && (!isset($_FILES[$item_id]) || (int) $_FILES[$item_id]['size'] == 0)) {
            // If remove is checked, remove it.
            if (isset($_POST[$item_id.'_remove'])) {
                $store = array();
            }else{
                // Else get the previous data and reuse it.
                if (is_object($Item)){

                    $json = PerchUtil::json_safe_decode($Item->itemJSON(), true);

                    if (PerchUtil::count($json) && $this->Tag->in_repeater() && $this->Tag->tag_context()) {
                        $waypoints = preg_split('/_([0-9]+)_/', $this->Tag->tag_context(), null, PREG_SPLIT_DELIM_CAPTURE);
                        if (PerchUtil::count($waypoints) > 0) {
                            $subject = $json;
                            foreach($waypoints as $waypoint) {
                                if (isset($subject[$waypoint])) {
                                    $subject = $subject[$waypoint];
                                }else{
                                    $subject = false;
                                }
                                $store = $subject;
                            }
                        }
                    }

                    if (PerchUtil::count($json) && isset($json[$this->Tag->id()])) {
                        $store = $json[$this->Tag->id()];
                    }
                }else if (is_array($Item)) {
                    $json = $Item;
                    if (PerchUtil::count($json) && isset($json[$this->Tag->id()])) {
                        $store = $json[$this->Tag->id()];
                    }
                }
            }
        }

        // log resources
        if (PerchUtil::count($store) && isset($store['path'])) {
            $Resources = new PerchResources;

            // Main image
            $parentID = $Resources->log($this->app_id, $store['bucket'], $store['path'], 0, 'orig', false, $store, $AssetMeta);

            // variants
            if (isset($store['sizes']) && PerchUtil::count($store['sizes'])) {
                foreach($store['sizes'] as $key=>$size) {
                    $Resources->log($this->app_id, $store['bucket'], $size['path'], $parentID, $key, false, $size, $AssetMeta);
                }
            }

            // Additional IDs from the session
            if (PerchSession::is_set('resourceIDs')) {
                $ids = PerchSession::get('resourceIDs');
                if (is_array($ids) && PerchUtil::count($ids)) {
                    $Resources->log_extra_ids($ids);
                }
                PerchSession::delete('resourceIDs');
            }
        }

        self::$file_paths = array();


        // Check it's not an empty array
        if (is_array($store) && count($store)===0) {
            return null;
        }

        return $store;
    }

    public function get_processed($raw=false)
    {
        $json = $raw;
        if (is_array($json)) {

            $item = $json;
            $orig_item = $item; // item gets overriden by a variant.

            if ($this->Tag->width() || $this->Tag->height()) {
                $variant_key = 'w'.$this->Tag->width().'h'.$this->Tag->height().'c'.($this->Tag->crop() ? '1' : '0').($this->Tag->density() ? '@'.$this->Tag->density().'x': '');
                if (isset($json['sizes'][$variant_key])) {
                    $item = $json['sizes'][$variant_key];
                } else {
                    //PerchUtil::debug('Missing variant.');
                    //  This is a bad idea. If there are lots of images, they can't all be resized in the same process.
                    //$item = $this->_generate_variant_on_the_fly($variant_key, $orig_item, $this->Tag);
                }
            }

            if ($this->Tag->output() && $this->Tag->output()!='path') {
                switch($this->Tag->output()) {
                    case 'size':
                        return isset($item['size']) ? $item['size'] : 0;

                    case 'h':
                    case 'height':
                        return isset($item['h']) ? $item['h'] : 0;

                    case 'w':
                    case 'width':
                        return isset($item['w']) ? $item['w'] : 0;

					case 'filename':
						return $item['path'];

                    case 'mime':
                        return $item['mime'];

                    case 'tag':

                        $attrs = [];

                        $tags = array('class', 'title', 'alt');
                        $dont_escape = array();

                        foreach($tags as $tag) {
                            if ($this->Tag->$tag()) {
                                $val = $this->Tag->$tag();
                                if (substr($val, 0, 1)=='{' && substr($val, -1)=='}') {
                                    $attrs[$tag] = '<'.$this->Tag->tag_name().' id="'.str_replace(array('{','}'), '', $val).'" escape="true" />';
                                    $dont_escape[] = $tag;
                                }else{
                                    $attrs[$tag] = PerchUtil::html($val, true);
                                }
                            }
                        }

                        $this->processed_output_is_markup = true;

                        if (isset($orig_item['mime']) && strpos($orig_item['mime'], 'image') === false) {

                            $attrs['href'] = $this->_get_image_src($orig_item, $item);

                            $r =  PerchXMLTag::create('a', 'opening', $attrs, $dont_escape);
                            $r .=  ($this->Tag->is_set('title') ? $this->Tag->title() : $orig_item['title']);
                            $r .= PerchXMLTag::create('a', 'closing');
                            return $r;

                        } else {
                            // include inline?
                            if ($this->Tag->include() == 'inline' && isset($item['mime'])) {
                                if (strpos($item['mime'], 'svg')) {
                                    return file_get_contents($this->_get_image_file($orig_item, $item));
                                    break;
                                }
                            }

                            $attrs['src'] = $this->_get_image_src($orig_item, $item);

                            if (!PERCH_RWD) {
                                $attrs['width']  = isset($item['w']) ? $item['w'] : '';
                                $attrs['height'] = isset($item['h']) ? $item['h'] : '';
                            }

                            if (!isset($attrs['alt'])) {
                                $attrs['alt'] = $orig_item['title'];
                            }

                            return PerchXMLTag::create('img', 'single', $attrs, $dont_escape);
        
                        }

                    case 'nextgen':

                        $attrs = [];

                        $tags = array('class', 'title', 'alt');
                        $dont_escape = array();

                        $base_img_tag = '';

                        foreach($tags as $tag) {
                            if ($this->Tag->$tag()) {
                                $val = $this->Tag->$tag();
                                if (substr($val, 0, 1)=='{' && substr($val, -1)=='}') {
                                    $attrs[$tag] = '<'.$this->Tag->tag_name().' id="'.str_replace(array('{','}'), '', $val).'" escape="true" />';
                                    
                                    $dont_escape[] = $tag;
                                }else{
                                    $attrs[$tag] = PerchUtil::html($val, true);
                                }
                            }
                        }

                        $this->processed_output_is_markup = true;

                        if (isset($orig_item['mime']) && strpos($orig_item['mime'], 'image') === false) {

                            $attrs['href'] = $this->_get_image_src($orig_item, $item);

                            $r =  PerchXMLTag::create('a', 'opening', $attrs, $dont_escape);
                            $r .=  ($this->Tag->is_set('title') ? $this->Tag->title() : $orig_item['title']);
                            $r .= PerchXMLTag::create('a', 'closing');
                            $base_img_tag = $r;

                        } else {
                            // include inline?
                            if ($this->Tag->include() == 'inline' && isset($item['mime'])) {
                                if (strpos($item['mime'], 'svg')) {
                                    $base_img_tag = file_get_contents($this->_get_image_file($orig_item, $item));
                                    break;
                                }
                            }

                            $attrs['src'] = $this->_get_image_src($orig_item, $item);

                            if (!PERCH_RWD) {
                                $attrs['width']  = isset($item['w']) ? $item['w'] : '';
                                $attrs['height'] = isset($item['h']) ? $item['h'] : '';
                            }

                            if (!isset($attrs['alt'])) {
                                $attrs['alt'] = $orig_item['alt'];
                            }

                            $base_img_tag = PerchXMLTag::create('img', 'single', $attrs, $dont_escape);
        
                            if (strpos($item['mime'], 'svg')) {
                                return $base_img_tag;
                            }
                        }


                        $imgAttrs = [];

                        $tags = ['class','title','alt'];

                        $out = '';

                        $out .= PerchXMLTag::create('picture','opening',[]);

                        // webp <source> tag
                        $out .= PerchXMLTag::create('source','single', [
                            'type' => 'image/webp',
                            'srcset' => $this->_get_image_src($orig_item, $item) . '.webp'
                        ]);
                        
                        // add img
                        $out .= $base_img_tag;

                        // closing tag
                        $out .= PerchXMLTag::create('picture','closing',[]);

                        return $out;
                    }
            }

            return $this->_get_image_src($orig_item, $item);

        }

        if ($this->Tag->width() || $this->Tag->height()) {
            $PerchImage = new PerchImage;
            return $PerchImage->get_resized_filename($raw, $this->Tag->width(), $this->Tag->height());
        }



        return PERCH_RESPATH.'/'.str_replace(PERCH_RESPATH.'/', '', $raw);
    }

    public function render_admin_listing($details=false)
    {
        $s = '';

        if (is_array($details)) {

            if ($this->Tag->output()) {
                return $this->get_processed($details);
            }

            $PerchImage = new PerchImage;

            $json = $details;

            $Bucket = PerchResourceBuckets::get($json['bucket']);

            if (isset($json['sizes']['thumb'])) {
                $image_src  = $json['sizes']['thumb']['path'];
                $image_w    = $json['sizes']['thumb']['w'];
                $image_h    = $json['sizes']['thumb']['h'];
            }

            $image_path = PerchUtil::file_path($Bucket->get_file_path().'/'.$image_src);

            if (file_exists($image_path)) {
                $s .= '<img src="'.PerchUtil::html($Bucket->get_web_path().'/'.$image_src).'" width="'.($image_w/2).'" height="'.($image_h/2).'" alt="Preview">';
            }
        }

        return $s;
    }

    private function _generate_variant_on_the_fly($variant_key, $orig, $Tag)
    {
        //PerchUtil::debug($orig);

        if (isset($orig['bucket'])) {
            $Bucket = PerchResourceBuckets::get($orig['bucket']);
        }else{
            $Bucket = PerchResourceBuckets::get($Tag->bucket());
        }

        $file_path = PerchUtil::file_path($Bucket->get_file_path().'/'.str_replace($Bucket->get_file_path().'/', '', $orig['path']));

        $PerchImage = new PerchImage;
        if ($Tag->quality()) $PerchImage->set_quality($Tag->quality());
        if ($Tag->is_set('sharpen')) $PerchImage->set_sharpening($Tag->sharpen());
        if ($Tag->density()) $PerchImage->set_density($Tag->density());

        $result = $PerchImage->resize_image($file_path, $Tag->width(), $Tag->height(), $Tag->crop());

        //PerchUtil::debug($result, 'error');

        if ($result) {
            $item = $result;

            $item['target_w'] = $Tag->width();
            $item['target_h'] = $Tag->height();
            $item['density']  = ($Tag->density() ? $Tag->density() : '1');
            $item['path']     = $item['file_name'];
            $item['size']     = filesize($item['file_path']);
            $item['mime']     = (isset($item['mime']) ? $item['mime'] : '');

            if ($item && isset($item['_resourceID'])) {
                $item['assetID'] = $item['_resourceID'];
            }

            $Assets    = new PerchAssets_Assets;
            $Asset     = $Assets->find($orig['assetID']);

            if ($Asset) {
                $Asset->add_new_size_variant($variant_key, $item);
            }


            return $item;
        }

        return 'bother';
    }

    private function _get_image_src($orig_item, $item)
    {
        //PerchUtil::debug($orig_item, 'success');
        //PerchUtil::debug($item, 'notice');

        if (!isset($item['path'])) return false;

        if (isset($orig_item['bucket'])) {
            $Bucket = PerchResourceBuckets::get($orig_item['bucket']);
        }else{
            $Bucket = PerchResourceBuckets::get($this->Tag->bucket());
        }

        return $Bucket->get_web_path().'/'.str_replace($Bucket->get_web_path().'/', '', $item['path']);
    }

    private function _get_image_file($orig_item, $item)
    {

        if (isset($orig_item['bucket'])) {
            $Bucket = PerchResourceBuckets::get($orig_item['bucket']);
        }else{
            $Bucket = PerchResourceBuckets::get($this->Tag->bucket());
        }

        return PerchUtil::file_path($Bucket->get_file_path().'/'.str_replace($Bucket->get_file_path().'/', '', $item['path']));
    }

    private function _file_is_acceptable($file)
    {   
        if (!PERCH_VERIFY_UPLOADS) return true;

        if (isset($file['error'])) {
            if ($file['error']!=UPLOAD_ERR_OK) {
                return false;
            }
        }

        $File = new PerchAssetFile(array(
                        'file_path' => $file['tmp_name'],
                        'file_name' => $file['name'],
                        'size'      => $file['size'],
                    ));

        $result = $File->is_acceptable_upload($this->Tag, $this->accept_types);

        if (!$result) PerchUtil::debug($File->get_errors(), 'notice');

        #error_log(print_r($File->get_errors(), 1));

        return $result;

    }

    private function to_webp_with_gd($image_path, $save_as, $quality = 80)
    {
        $info = getimagesize($image_path);
        if (!is_array($info)) return false;

        $mime = $info['mime'];

        $orig_image = null;

        switch ($mime) {
            case 'image/jpeg':
                $orig_image = imagecreatefromjpeg($image_path);
                break;
            case 'image/gif':
                $orig_image = imagecreatefromgif($image_path);
                break;
            case 'image/png':
                $orig_image = imagecreatefrompng($image_path);
                break;
            case 'image/webp':
                $orig_image = imagecreatefromwebp($image_path);
                break;
            default:
                $orig_image = imagecreatefromjpeg($image_path);
                break;
        }

        return imagewebp($orig_image, $save_as, $quality);
    }
}
function console_log( $data ){
    echo '<script>';
    echo 'console.log('. json_encode( $data ) .')';
    echo '</script>';
  }