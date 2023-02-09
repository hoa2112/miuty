<?php
namespace App\Libs;

class StringLib{
/*--------------------------------------------------------------------------*/
/* 							CLEANER STRING FUNCTIONS						*/
/*--------------------------------------------------------------------------*/
	
	static function strippedLink($str, $length=68){
		if ( (strlen($str) - $length ) < 3 ){
			return $str;
		}
		return substr( $str , 0, floor(($length-3)/2) ).'...'.substr( $str , -floor(($length-3)/2) );
	}

	// ham tao descprtion trong tag DESCRIPTION
	static function descriptionText($str){
		$meta_desc = self::post_db_parse_html($str);
		$meta_desc = self::plainText($meta_desc);
		$meta_desc = html_entity_decode($meta_desc,ENT_QUOTES,"UTF-8");
		$meta_desc = str_replace('\'','',$meta_desc);
		$meta_desc = str_replace('"','',$meta_desc);
		return $meta_desc;
	}

	static function plainText($str){
		$str = strip_tags($str);
		$str = self::trimSpace(str_replace(array(chr(13),chr(9),chr(10),chr(239))," ",$str));
		$str = self::delDoubleSpace($str);
        $str = html_entity_decode($str);

        return $str;
	}


	static function base64_url_encode($input) {
		return str_replace('=','',strtr(base64_encode($input), '+/', '-_'));
	}

	static function make_safe_name($name,$replace_string="_"){
		return preg_replace( "/[^\w\.]/", $replace_string , $name );
	}

	static function convertUnicodeCase($test){
		$uppercase_utf8=array("A","Á","À","Ả","Ã","Ạ","Â","Ấ","Ầ","Ẩ","Ẫ","Ậ","Ă","Ắ","Ằ","Ẳ","Ẵ","Ặ","E","É","È","Ẻ","Ẽ","Ẹ","Ê","Ế","Ề","Ể","Ễ","Ệ","I","Í","Ì","Ỉ","Ĩ","Ị","O","Ó","Ò","Ỏ","Õ","Ọ","Ô","Ố","Ồ","Ổ","Ỗ","Ộ","Ơ","Ớ","Ờ","Ở","Ỡ","Ợ","U","Ú","Ù","Ủ","Ũ","Ụ","Ư","Ứ","Ừ","Ử","Ữ","Ự","Y","Ý","Ỳ","Ỷ","Ỹ","Ỵ","Đ"," ", "~", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "=", "|", "\\", "{", "}",":", ";", "<", ">", "/", "?");
		$lowercase_utf8=array("a","á","à","ả","ã","ạ","â","ấ","ầ","ẩ","ẫ","ậ","ă","ắ","ằ","ẳ","ẵ","ặ","e","é","è","ẻ","ẽ","ẹ","ê","ế","ề","ể","ễ","ệ","i","í","ì","ỉ","ĩ","ị","o","ó","ò","ỏ","õ","ọ","ô","ố","ồ","ổ","ỗ","ộ","ơ","ớ","ờ","ở","ỡ","ợ","u","ú","ù","ủ","ũ","ụ","ư","ứ","ừ","ử","ữ","ự","y","ý","ỳ","ỷ","ỹ","ỵ","đ","", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","", "", "", "", "", "", "", "", "");
		if(!$test){
			return $test;
		} else {
			$new_test=str_replace($uppercase_utf8,$lowercase_utf8,$test);
			return $new_test;
		}
	}
	
    static function convertUnicodeCaseWithoutHtml($test){
		$uppercase_utf8=array("A","Á","À","Ả","Ã","Ạ","Â","Ấ","Ầ","Ẩ","Ẫ","Ậ","Ă","Ắ","Ằ","Ẳ","Ẵ","Ặ","E","É","È","Ẻ","Ẽ","Ẹ","Ê","Ế","Ề","Ể","Ễ","Ệ","I","Í","Ì","Ỉ","Ĩ","Ị","O","Ó","Ò","Ỏ","Õ","Ọ","Ô","Ố","Ồ","Ổ","Ỗ","Ộ","Ơ","Ớ","Ờ","Ở","Ỡ","Ợ","U","Ú","Ù","Ủ","Ũ","Ụ","Ư","Ứ","Ừ","Ử","Ữ","Ự","Y","Ý","Ỳ","Ỷ","Ỹ","Ỵ","Đ","Q","W","R","T","U","S","D","F","G","H","J","K","L","Z","X","C","V","B","N","M","P");
		$lowercase_utf8=array("a","á","à","ả","ã","ạ","â","ấ","ầ","ẩ","ẫ","ậ","ă","ắ","ằ","ẳ","ẵ","ặ","e","é","è","ẻ","ẽ","ẹ","ê","ế","ề","ể","ễ","ệ","i","í","ì","ỉ","ĩ","ị","o","ó","ò","ỏ","õ","ọ","ô","ố","ồ","ổ","ỗ","ộ","ơ","ớ","ờ","ở","ỡ","ợ","u","ú","ù","ủ","ũ","ụ","ư","ứ","ừ","ử","ữ","ự","y","ý","ỳ","ỷ","ỹ","ỵ","đ","q","w","r","t","p","s","d","f","g","h","j","k","l","z","x","c","v","b","n","m","p");
		if(!$test){
			return $test;
		} else {
			$new_test=str_replace($uppercase_utf8,$lowercase_utf8,$test);
			return $new_test;
		}
	}
    
	static function safe_title($text, $lower_case = true){
		$text = self::post_db_parse_html($text);
		$text = self::stripUnicode($text);
		$text = self::_name_cleaner($text,"-");
		$text = str_replace("----","-",$text);
		$text = str_replace("---","-",$text);
		$text = str_replace("--","-",$text);
		$text = trim($text, '-');

		if($text){
			return $lower_case ? strtolower($text) : $text;
		}
		return "";
	}
    
    static function _name_cleaner($name,$replace_string="_"){
		return preg_replace( "/[^a-zA-Z0-9\-\_]/", $replace_string , $name );
	}

	static function stripUnicode($str = ''){
		if($str != ''){
			$marTViet = array("à","á","ạ","ả","ã","â","ầ","ấ","ậ","ẩ","ẫ","ă","ằ","ắ","ặ","ẳ","ẵ","è","é","ẹ","ẻ","ẽ","ê","ề","ế","ệ","ể","ễ","ì","í","ị","ỉ","ĩ","ò","ó","ọ","ỏ","õ","ô","ồ","ố","ộ","ổ","ỗ","ơ","ờ","ớ","ợ","ở","ỡ","ù","ú","ụ","ủ","ũ","ư","ừ","ứ","ự","ử","ữ","ỳ","ý","ỵ","ỷ","ỹ","đ","À","Á","Ạ","Ả","Ã","Â","Ầ","Ấ","Ậ","Ẩ","Ẫ","Ă","Ằ","Ắ","Ặ","Ẳ","Ẵ","È","É","Ẹ","Ẻ","Ẽ","Ê","Ề","Ế","Ệ","Ể","Ễ","Ì","Í","Ị","Ỉ","Ĩ","Ò","Ó","Ọ","Ỏ","Õ","Ô","Ồ","Ố","Ộ","Ổ","Ỗ","Ơ","Ờ","Ớ","Ợ","Ở","Ỡ","Ù","Ú","Ụ","Ủ","Ũ","Ư","Ừ","Ứ","Ự","Ử","Ữ","Ỳ","Ý","Ỵ","Ỷ","Ỹ","Đ");
			$marKoDau = array("a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","e","e","e","e","e","e","e","e","e","e","e","i","i","i","i","i","o","o","o","o","o","o","o","o","o","o","o","o","o","o","o","o","o","u","u","u","u","u","u","u","u","u","u","u","y","y","y","y","y","d","A","A","A","A","A","A","A","A","A","A","A","A","A","A","A","A","A","E","E","E","E","E","E","E","E","E","E","E","I","I","I","I","I","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","U","U","U","U","U","U","U","U","U","U","U","Y","Y","Y","Y","Y","D");
			$str = str_replace($marTViet,$marKoDau,$str);
		}
		return $str;
	}

	static function clean_key($key){
		if ($key != ""){
			$key = htmlspecialchars(urldecode($key));
			$key = preg_replace( "/\.\./", ""  , $key );
			$key = preg_replace( "/\_\_(.+?)\_\_/"  , ""  , $key );
			$key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );
		}
		return $key;
	}

	static function clean_value($val){
		if ($val != ""){
			$val = trim($val);
			$val = str_replace( "&#032;", " ", $val );
			$val = str_replace( chr(0xCA), "", $val );  //Remove sneaky spaces
			$val = str_replace(array("<!--", "-->", "/<script/i", ">", "<", '"', "/\\\$/", "/\r/", "!", "'"), array("","","&#60;script","&gt;", "&lt;","&quot;","&#036;","","&#33;","&#39;"), $val);
			$get_magic_quotes = @get_magic_quotes_gpc();
			if ( $get_magic_quotes ){
				$val = stripslashes($val);
			}
			$val = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $val );
		}
		return $val;
	}

	static function post_db_parse_html($t=""){
		if($t != ""){
			//bo cac dau dac biet di
			$t = str_replace(array("&#39;", "&#33;", "&#036;", "&#124;", "&amp;", "&gt;", "&lt;", "&quot;"), array("'", "!", "$", "|", "&", ">", "<", '"'), $t);

			//bo cac the dac biet di
			$t = preg_replace(array("/javascript/i", "/alert/i", "/about:/i", "/onmouseover/i", "/onmouseout/i", "/onclick/i", "/onload/i", "/onsubmit/i","/applet/i","/meta/i"),array("j&#097;v&#097;script", "&#097;lert", "&#097;bout:", "&#111;nmouseover", "&#111;nmouseout", "&#111;nclick", "&#111;nload", "&#111;nsubmit", "&#097;pplet", "met&#097;"), $t );
		}
		return $t;
	}
	
	static function trimSpace($str = ""){
		if($str != ""){
			$str = str_replace("&nbsp;", " ", $str);
			$str = preg_replace('![\t ]*[\r\n]+[\t ]*!', ' ', $str);
			$str = trim($str);
		}
		return $str;
	}

	static function delDoubleSpace($str){
		return preg_replace('/  {2,}/',' ',$str);
	}

	static function trimHtml($str){
        $str = self::trimSpace($str);
	    $str = self::delDoubleSpace($str);
	    return $str;
    }

/*--------------------------------------------------------------------------*/
/* 							PARSE Emoticon									*/
/*--------------------------------------------------------------------------*/

	public static function parseEmoticon($text,$direct = false){
		if(trim($text) == '') return '';

		$emoticon_dir = ($direct?WEB_ROOT:'').'style/images/smiley/';
		$emoticon_dir = '<img border="0" src="'.$emoticon_dir;

		$arr_replace = array(
			'/;;\)/is'	=>	$emoticon_dir.'5.gif" />'
			,'/;\)\)/is'=>	$emoticon_dir.'6.gif" />'
			,'/>:D</is'	=>	$emoticon_dir.'61.gif" />'
			,'/:-\//is'	=>	$emoticon_dir.'7.gif" alt=":-/" title=":-/" />'

			,'/:-\*/is'	=>	$emoticon_dir.'11.gif" alt=":-*" title=":-*" />'
			,'/=\(\(/is'=>	$emoticon_dir.'12.gif" alt="=((" title="=((" />'
			,'/:-O/is'	=>	$emoticon_dir.'13.gif" alt=":-O" title=":-O" />'

			,'/:>/is'	=>	$emoticon_dir.'15.gif" alt=":>" title=":>" />'
			,'/B-\)/is'	=>	$emoticon_dir.'16.gif" />'

			,'/#:-S/is'	=>	$emoticon_dir.'18.gif" />'
			,'/:-SS/is'	=>	$emoticon_dir.'42.gif" />'
			,'/:-S/is'	=>	$emoticon_dir.'17.gif" alt=":-S" title=":-S" />'

			,'/>:\)/is'	=>	$emoticon_dir.'19.gif" />'
			,'/:\(\(/is'=>	$emoticon_dir.'20.gif" />'
			,'/:\)\)/is'=>	$emoticon_dir.'21.gif" />'

			,'/\/:\)/is'=>	$emoticon_dir.'23.gif" />'
			,'/=\)\)/is'=>	$emoticon_dir.'24.gif" alt="=))" title="=))" />'
			,'/O:-\)/is'=>	$emoticon_dir.'25.gif" alt="O:-)" title="O:-)" />'

			,'/=;/is'	=>	$emoticon_dir.'27.gif" alt="=;" title="=;" />'
			,'/I-\)/is'	=>	$emoticon_dir.'28.gif" alt="I-)" title="I-)" />'
			,'/8-\|/is'	=>	$emoticon_dir.'29.gif" alt="8-|" title="8-|" />'
			,'/L-\)/is'	=>	$emoticon_dir.'30.gif" alt="L-)" title="L-)" />'
			,'/:-&/is'	=>	$emoticon_dir.'31.gif" alt=":-&" title=":-&" />'
			,'/:-\$/is'	=>	$emoticon_dir.'32.gif" alt=":-$" title=":-$" />'
			,'/\[-\(/is'=>	$emoticon_dir.'33.gif" alt="[-(" title="[-(" />'
			,'/:O\)/is'	=>	$emoticon_dir.'34.gif" alt=":O)" title=":O)" />'
			,'/8-\}/is'	=>	$emoticon_dir.'35.gif" alt="8-}" title="8-}" />'
			,'/<:-P/is'	=>	$emoticon_dir.'36.gif" />'
			,'/\(:\|/is'=>	$emoticon_dir.'37.gif" />'
			,'/=P\~/is'	=>	$emoticon_dir.'38.gif" alt="=P~" title="=P~" />'
			,'/:-\?/is'	=>	$emoticon_dir.'39.gif" alt=":-?" title=":-?" />'
			,'/#-o/is'	=>	$emoticon_dir.'40.gif" alt="#-o" title="#-o" />'
			,'/=D>/is'	=>	$emoticon_dir.'41.gif" alt="=D>" title="=D>" />'

			,'/@-\)/is'	=>	$emoticon_dir.'43.gif" alt="@-)" title="@-)" />'
			,'/:\^o/is'	=>	$emoticon_dir.'44.gif" alt=":^o" title=":^o" />'
			,'/:-w/is'	=>	$emoticon_dir.'45.gif" alt=":-w" title=":-w" />'
			,'/:-</is'	=>	$emoticon_dir.'46.gif" alt=":-<" title=":-<" />'
			,'/>:P/is'	=>	$emoticon_dir.'47.gif" />'
			,'/<\):\)/is'	=>	$emoticon_dir.'48.gif" />'
			,'/\^#\(\^/is'	=>	$emoticon_dir.'49.gif" alt="^#(^" title="^#(^" />'
			,'/:\)\]/is'	=>	$emoticon_dir.'50.gif" />'
			,'/:-c/is'	=>	$emoticon_dir.'51.gif" alt=":-c" title=":-c" />'
			,'/\~X\(/is'=>	$emoticon_dir.'52.gif" />'
			,'/:-h/is'	=>	$emoticon_dir.'53.gif" alt=":-h" title=":-h" />'
			,'/:-t/is'	=>	$emoticon_dir.'54.gif" alt=":-t" title=":-t" />'
			,'/8->/is'	=>	$emoticon_dir.'55.gif" alt="8->" title="8->" />'
			,'/X_X/is'	=>	$emoticon_dir.'56.gif" alt="X_X" title="X_X" />'
			,'/:!!/is'	=>	$emoticon_dir.'57.gif" alt=":!!" title=":!!" />'
			,'/\\\m\//is'	=>	$emoticon_dir.'58.gif" alt="\m/" title="\m/" />'
			,'/:-q/is'	=>	$emoticon_dir.'59.gif" alt=":-q" title=":-q" />'
			,'/:-bd/is'	=>	$emoticon_dir.'60.gif" />'
			,'/:x/is'	=>	$emoticon_dir.'8.gif" alt=":x" title=":x" />'
			,'/:">/is'	=>	$emoticon_dir.'9.gif" alt=":&quot;>" title=":&quot;>" />'

			,'/X\(/is'	=>	$emoticon_dir.'14.gif" alt="X(" title="X(" />'
			,'/:-B/is'	=>	$emoticon_dir.'26.gif" alt=":-B" title=":-B" />'
			,'/:\)/is'	=>	$emoticon_dir.'1.gif" alt=":)" title=":)" />'
			,'/:\(/is'	=>	$emoticon_dir.'2.gif" alt=":(" title=":(" />'
			,'/;\)/is'	=>	$emoticon_dir.'3.gif" alt=";)" title=";)" />'
			,'/:D/is'	=>	$emoticon_dir.'4.gif" alt=":D" title=":D" />'
			,'/:\|/is'	=>	$emoticon_dir.'22.gif" alt=":|" title=":|" />'
			,'/:P/is'	=>	$emoticon_dir.'10.gif" alt=":P" title=":P" />'
		);

		$text_replace = array();
		foreach (array_keys($arr_replace) as $value){
			$text_replace[] = htmlspecialchars($value);
		}
		$text = preg_replace($text_replace,$arr_replace,$text);

		return $text;
	}

    public static function upcaseFirstChar($str = ''){
		if($str != ''){
			if(strpos($str,' ')){
				$first = mb_substr($str, 0,strpos($str,' '));
				$last = mb_substr($str, strpos($str,' '), strlen($str));
				$str = mb_convert_case($first, MB_CASE_TITLE, "UTF-8").$last;
			}
			else{
				$str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
			}
		}
		return $str;
	}

    public static function str_limit($str, $limit = 60, $strip_tags = true){
        if($strip_tags) {
            $str = trim(strip_tags($str));
        }
        if( mb_strlen($str, "utf-8") > $limit ) {
            $surfix = "...";
            $strTmp = mb_substr($str, 0, $limit, "utf-8");
            $intPos = mb_strrpos($strTmp, " ", 0, "utf-8");
            $str = mb_substr($strTmp, 0, $intPos, 'utf-8').$surfix;
        }
        return $str;
    }
}
