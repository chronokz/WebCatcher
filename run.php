<?php
/* Configurations */
$url = 'http://some-domain.com/url-path/some-page.html';
$allow_extentions = array('css', 'js', 'jpg', 'png', 'gif', 'svg', 'ttf', 'woff', 'woff2', 'eot');
set_time_limit(180); // 3 minutes


$site = get_file($url);
$htmlfile = ext($url, '/');

if (!strpos($htmlfile, '.'))
	$htmlfile = 'index.html';

file_put_contents($htmlfile, $site);
echo_log('The page "'. $htmlfile.'" was created');

function get_file($url)
{
	$context = stream_context_create(array("http" => array("header" => "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36")));
	@$contents = file_get_contents($url, false, $context);
	if (!$contents)
		echo_log('File "'.$url.'" not found');
	return $contents;
}

function echo_log($text)
{
	if ($_SERVER['HTTP_USER_AGENT'])
	{
		echo $text.'<br>';
	}
	else
	{
		echo $text."\n\n";
	}
}

function update_file($filename, $find, $replace)
{
	$content = get_file($filename);
	$content = str_replace($find, $replace, $content);
	file_put_contents($filename, $content);
}


function create_folders($folders)
{
	$path = '';
	foreach ($folders as $folder)
	{
		$path .= $folder.'/';
		if(!is_dir($path))
			mkdir($path, 777);
	}
}

function create_folder_path($file_path)
{
	$folders = explode('/', $file_path);
	unset($folders[count($folders)-1]);
	$dir = join('/',$folders);
	if(!is_dir($dir))
	{
		create_folders($folders);
		echo_log('The folder "'. $dir.'" was created');
	}
}

function ext($filename, $delimter = '.')
{
	return array_pop(explode($delimter, $filename));
}


function search_files_in_css($matches)
{
	global $url, $entity;
	$file_url = $matches[1];

	if (substr($file_url, 0, 8) != 'https://' && substr($file_url, 0, 7) != 'http://')
	{

		if (substr($file_url, 0, 1) == '/')
		{
			$filepath = substr($file_url, 1);
			if (strpos($filepath, '#'))
				$filepath = substr($filepath, 0, strrpos($filepath,'#'));
			if (strpos($filepath, '?'))
				$filepath = substr($filepath, 0, strrpos($filepath,'?'));

			$urlparse = parse_url($url);
			$filelink = $urlparse['scheme'] . '://' . $urlparse['host'] . $file_url;
		}
		else
		{
			$current_folder = substr($entity, 0, strrpos($entity,'/') + 1);
			$filepath = $current_folder . $file_url;
			if (strpos($filepath, '#'))
				$filepath = substr($filepath, 0, strrpos($filepath,'#'));
			if (strpos($filepath, '?'))
				$filepath = substr($filepath, 0, strrpos($filepath,'?'));
			$filelink = substr($url, 0, strrpos($url,'/') + 1) . $filepath;
		}

		echo_log('url:'.$file_url);

		create_folder_path($filepath);
		$file = get_file($filelink);
		file_put_contents($filepath, $file);

	}
}


function search_files($matches)
{
	global $allow_extentions, $url, $entity, $htmlfile;

	$entity = $matches[2];
	$extention = ext($entity);

	if (in_array($extention, $allow_extentions))
	{
		if (substr($entity, 0, 8) == 'https://' || substr($entity, 0, 7) == 'http://')
		{
			$filename = ext($entity, '/');
			$filepath = 'vendor/'.array_shift(explode('.', $filename)).'/'.$filename;
			$filelink = $entity;
		}
		else if(substr($entity, 0, 2) == '//')
		{
			$entity = 'http:'.$entity;
			$filename = ext($entity, '/');
			$filepath = 'vendor/'.array_shift(explode('.', $filename)).'/'.$filename;
			$filelink = $entity;
		}
		else if(substr($entity, 0, 1) == '/')
		{
			$filepath = substr($entity, 1);
			$urlparse = parse_url($url);
			$filelink = $urlparse['scheme'] . '://' . $urlparse['host'] . $entity;

			update_file($htmlfile, $entity, $filepath);
		}
		else
		{
			if (substr($entity, 0, 2) == './')
			{
				$entity = substr($entity, 2);
			}

			$filepath = $entity;
			$filelink = substr($url, 0, strrpos($url,'/') + 1) . $entity;
		}

		echo_log('url:'.$entity);

		create_folder_path($filepath);
		$file = get_file($filelink);
		file_put_contents($filepath, $file);

		if (ext($entity) == 'css')
		{
			preg_replace_callback('/url\(["\']?([\/\w\:\.-]+\??\#?[\&\w=\.\-]+)["\']?\)/im', 'search_files_in_css', $file);
		}

	}
}

preg_replace_callback('/(href|src)=["\']?([\/\w\:\.-]+)["\']?/im', 'search_files', $site);

?>