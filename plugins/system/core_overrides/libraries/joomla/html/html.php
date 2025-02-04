<?php
/**
 * @package     Joomla.Platform
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JHtml::addIncludePath(JPATH_PLATFORM . '/joomla/html/html');

jimport('joomla.environment.uri');
jimport('joomla.environment.browser');
jimport('joomla.filesystem.file');

/**
 * Utility class for all HTML drawing classes
 *
 * @package     Joomla.Platform
 * @subpackage  HTML
 * @since       11.1
 */
abstract class JHtml
{
	/**
	 * Option values related to the generation of HTML output. Recognized
	 * options are:
	 *     fmtDepth, integer. The current indent depth.
	 *     fmtEol, string. The end of line string, default is linefeed.
	 *     fmtIndent, string. The string to use for indentation, default is
	 *     tab.
	 *
	 * @var    array
	 * @since  11.1
	 */
	public static $formatOptions = array('format.depth' => 0, 'format.eol' => "\n", 'format.indent' => "\t");

	/**
	 * An array to hold included paths
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected static $includePaths = array();

	/**
	 * An array to hold method references
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected static $registry = array();

	/**
	 * Method to extract a key
	 *
	 * @param   string  $key  The name of helper method to load, (prefix).(class).function
	 *                        prefix and class are optional and can be used to load custom html helpers.
	 *
	 * @return  array  Contains lowercase key, prefix, file, function.
	 *
	 * @since   11.1
	 */
	protected static function extract($key)
	{
		$key = preg_replace('#[^A-Z0-9_\.]#i', '', $key);

		// Check to see whether we need to load a helper file
		$parts = explode('.', $key);

		$prefix = (count($parts) == 3 ? array_shift($parts) : 'JHtml');
		$file = (count($parts) == 2 ? array_shift($parts) : '');
		$func = array_shift($parts);

		return array(strtolower($prefix . '.' . $file . '.' . $func), $prefix, $file, $func);
	}

	/**
	 * Class loader method
	 *
	 * Additional arguments may be supplied and are passed to the sub-class.
	 * Additional include paths are also able to be specified for third-party use
	 *
	 * @param   string  $key  The name of helper method to load, (prefix).(class).function
	 *                        prefix and class are optional and can be used to load custom
	 *                        html helpers.
	 *
	 * @return  mixed  JHtml::call($function, $args) or False on error
	 *
	 * @since   11.1
	 */
	public static function _($key)
	{
		list($key, $prefix, $file, $func) = self::extract($key);
		if (array_key_exists($key, self::$registry))
		{
			$function = self::$registry[$key];
			$args = func_get_args();
			// Remove function name from arguments
			array_shift($args);
			return JHtml::call($function, $args);
		}

		$className = $prefix . ucfirst($file);

		if (!class_exists($className))
		{
			jimport('joomla.filesystem.path');
			if ($path = JPath::find(JHtml::$includePaths, strtolower($file) . '.php'))
			{
				require_once $path;

				if (!class_exists($className))
				{
					JError::raiseError(500, JText::sprintf('JLIB_HTML_ERROR_NOTFOUNDINFILE', $className, $func));
					return false;
				}
			}
			else
			{
				JError::raiseError(500, JText::sprintf('JLIB_HTML_ERROR_NOTSUPPORTED_NOFILE', $prefix, $file));
				return false;
			}
		}

		$toCall = array($className, $func);
		if (is_callable($toCall))
		{
			JHtml::register($key, $toCall);
			$args = func_get_args();
			// Remove function name from arguments
			array_shift($args);
			return JHtml::call($toCall, $args);
		}
		else
		{
			JError::raiseError(500, JText::sprintf('JLIB_HTML_ERROR_NOTSUPPORTED', $className, $func));
			return false;
		}
	}

	/**
	 * Registers a function to be called with a specific key
	 *
	 * @param   string  $key       The name of the key
	 * @param   string  $function  Function or method
	 *
	 * @return  boolean  True if the function is callable
	 *
	 * @since   11.1
	 */
	public static function register($key, $function)
	{
		list($key) = self::extract($key);
		if (is_callable($function))
		{
			self::$registry[$key] = $function;
			return true;
		}
		return false;
	}

	/**
	 * Removes a key for a method from registry.
	 *
	 * @param   string  $key  The name of the key
	 *
	 * @return  boolean  True if a set key is unset
	 *
	 * @since   11.1
	 */
	public static function unregister($key)
	{
		list($key) = self::extract($key);
		if (isset(self::$registry[$key]))
		{
			unset(self::$registry[$key]);
			return true;
		}

		return false;
	}

	/**
	 * Test if the key is registered.
	 *
	 * @param   string  $key  The name of the key
	 *
	 * @return  boolean  True if the key is registered.
	 *
	 * @since   11.1
	 */
	public static function isRegistered($key)
	{
		list($key) = self::extract($key);
		return isset(self::$registry[$key]);
	}

	/**
	 * Function caller method
	 *
	 * @param   string  $function  Function or method to call
	 * @param   array   $args      Arguments to be passed to function
	 *
	 * @return  mixed   Function result or false on error.
	 *
	 * @see     http://php.net/manual/en/function.call-user-func-array.php
	 * @since   11.1
	 */
	protected static function call($function, $args)
	{
		if (is_callable($function))
		{
			// PHP 5.3 workaround
			$temp = array();
			foreach ($args as &$arg)
			{
				$temp[] = &$arg;
			}
			return call_user_func_array($function, $temp);
		}
		else
		{
			JError::raiseError(500, JText::_('JLIB_HTML_ERROR_FUNCTION_NOT_SUPPORTED'));
			return false;
		}
	}

	/**
	 * Write a <a></a> element
	 *
	 * @param   string  $url      The relative URL to use for the href attribute
	 * @param   string  $text     The target attribute to use
	 * @param   array   $attribs  An associative array of attributes to add
	 *
	 * @return  string  <a></a> string
	 *
	 * @since   11.1
	 */
	public static function link($url, $text, $attribs = null)
	{
		if (is_array($attribs))
		{
			$attribs = JArrayHelper::toString($attribs);
		}

		return '<a href="' . $url . '" ' . $attribs . '>' . $text . '</a>';
	}

	/**
	 * Write a <iframe></iframe> element
	 *
	 * @param   string  $url       The relative URL to use for the src attribute
	 * @param   string  $name      The target attribute to use
	 * @param   array   $attribs   An associative array of attributes to add
	 * @param   string  $noFrames  The message to display if the iframe tag is not supported
	 *
	 * @return  string  <iframe></iframe> element or message if not supported
	 *
	 * @since   11.1
	 */
	public static function iframe($url, $name, $attribs = null, $noFrames = '')
	{
		if (is_array($attribs))
		{
			$attribs = JArrayHelper::toString($attribs);
		}

		return '<iframe src="' . $url . '" ' . $attribs . ' name="' . $name . '">' . $noFrames . '</iframe>';
	}

	/**
	 * Compute the files to be include
	 *
	 * @param   string   $file            path to file
	 * @param   boolean  $relative        path to file is relative to /media folder
	 * @param   boolean  $detect_browser  detect browser to include specific browser files
	 * @param   string   $folder          folder name to search into (images, css, js, ...)
	 *
	 * @return  array    files to be included
	 *
	 * @see     JBrowser
	 * @since   11.1
	 *
	 * @deprecated 12.1
	 */
	protected static function _includeRelativeFiles($file, $relative, $detect_browser, $folder)
	{
		JLog::add('JHtml::_includeRelativeFiles() is deprecated.  Use JHtml::includeRelativeFiles().', JLog::WARNING, 'deprecated');

		return self::includeRelativeFiles($folder, $file, $relative, $detect_browser, false);
	}

	/**
	 * Compute the files to be include
	 *
	 * @param   string   $folder          folder name to search into (images, css, js, ...)
	 * @param   string   $file            path to file
	 * @param   boolean  $relative        path to file is relative to /media folder
	 * @param   boolean  $detect_browser  detect browser to include specific browser files
	 * @param   boolean  $detect_debug    detect debug to include compressed files if debug is on
	 *
	 * @return  array    files to be included
	 *
	 * @see     JBrowser
	 * @since   11.1
	 */
	protected static function includeRelativeFiles($folder, $file, $relative, $detect_browser, $detect_debug)
	{
		// If http is present in filename
		if (strpos($file, 'http') === 0)
		{
			$includes = array($file);
		}
		else
		{
			// Extract extension and strip the file
			$strip		= JFile::stripExt($file);
			$ext		= JFile::getExt($file);

			// Detect browser and compute potential files
			if ($detect_browser)
			{
				$navigator = JBrowser::getInstance();
				$browser = $navigator->getBrowser();
				$major = $navigator->getMajor();
				$minor = $navigator->getMinor();

				// Try to include files named filename.ext, filename_browser.ext, filename_browser_major.ext, filename_browser_major_minor.ext
				// where major and minor are the browser version names
				$potential = array($strip, $strip . '_' . $browser,  $strip . '_' . $browser . '_' . $major,
					$strip . '_' . $browser . '_' . $major . '_' . $minor);
			}
			else
			{
				$potential = array($strip);
			}

			// If relative search in template directory or media directory
			if ($relative)
			{

				// Get the template
				$app = JFactory::getApplication();
				$template = $app->getTemplate();

				// Prepare array of files
				$includes = array();

				// For each potential files
				foreach ($potential as $strip)
				{
					$files = array();
					// Detect debug mode
					if ($detect_debug && JFactory::getConfig()->get('debug'))
					{
						$files[] = $strip . '-uncompressed.' . $ext;
					}
					$files[] = $strip . '.' . $ext;

					// Loop on 1 or 2 files and break on first found
					foreach ($files as $file)
					{
						// If the file is in the template folder
						if (file_exists(JPATH_THEMES . "/$template/$folder/$file"))
						{
							$includes[] = JURI::base(true) . "/templates/$template/$folder/$file";
							break;
						}
						else
						{
							// If the file contains any /: it can be in an media extension subfolder
							if (strpos($file, '/'))
							{
								// Divide the file extracting the extension as the first part before /
								list($extension, $file) = explode('/', $file, 2);

								// If the file yet contains any /: it can be a plugin
								if (strpos($file, '/'))
								{
									// Divide the file extracting the element as the first part before /
									list($element, $file) = explode('/', $file, 2);

									// Try to deal with plugins group in the media folder
									if (file_exists(JPATH_ROOT . "/media/$extension/$element/$folder/$file"))
									{
										$includes[] = JURI::root(true) . "/media/$extension/$element/$folder/$file";
										break;
									}
									// Try to deal with classical file in a a media subfolder called element
									elseif (file_exists(JPATH_ROOT . "/media/$extension/$folder/$element/$file"))
									{
										$includes[] = JURI::root(true) . "/media/$extension/$folder/$element/$file";
										break;
									}
									// Try to deal with system files in the template folder
									elseif (file_exists(JPATH_THEMES . "/$template/$folder/system/$element/$file"))
									{
										$includes[] = JURI::root(true) . "/templates/$template/$folder/system/$element/$file";
										break;
									}
									// Try to deal with system files in the media folder
									elseif (file_exists(JPATH_ROOT . "/media/system/$folder/$element/$file"))
									{
										$includes[] = JURI::root(true) . "/media/system/$folder/$element/$file";
										break;
									}
								}
								// Try to deals in the extension media folder
								elseif (file_exists(JPATH_ROOT . "/media/$extension/$folder/$file"))
								{
									$includes[] = JURI::root(true) . "/media/$extension/$folder/$file";
									break;
								}
								// Try to deal with system files in the template folder
								elseif (file_exists(JPATH_THEMES . "/$template/$folder/system/$file"))
								{
									$includes[] = JURI::root(true) . "/templates/$template/$folder/system/$file";
									break;
								}
								// Try to deal with system files in the media folder
								elseif (file_exists(JPATH_ROOT . "/media/system/$folder/$file"))
								{
									$includes[] = JURI::root(true) . "/media/system/$folder/$file";
									break;
								}
							}
							// Try to deal with system files in the media folder
							elseif (file_exists(JPATH_ROOT . "/media/system/$folder/$file"))
							{
								$includes[] = JURI::root(true) . "/media/system/$folder/$file";
								break;
							}
						}
					}
				}
			}
			// If not relative and http is not present in filename
			else
			{
				$includes = array();
				foreach ($potential as $strip)
				{
					// Detect debug mode
					if ($detect_debug && JFactory::getConfig()->get('debug') && file_exists(JPATH_ROOT . "/$strip-uncompressed.$ext"))
					{
						$includes[] = JURI::root(true) . "/$strip-uncompressed.$ext";
					}
					elseif (file_exists(JPATH_ROOT . "/$strip.$ext"))
					{
						$includes[] = JURI::root(true) . "/$strip.$ext";
					}
				}
			}
		}
		return $includes;
	}

	/**
	 * Write a <img></img> element
	 *
	 * @param   string   $file       The relative or absolute URL to use for the src attribute
	 * @param   string   $alt        The alt text.
	 * @param   string   $attribs    The target attribute to use
	 * @param   array    $relative   An associative array of attributes to add
	 * @param   boolean  $path_only  If set to true, it tries to find an override for the file in the template
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	public static function image($file, $alt, $attribs = null, $relative = false, $path_only = false)
	{
		if (is_array($attribs))
		{
			$attribs = JArrayHelper::toString($attribs);
		}

		$includes = self::includeRelativeFiles('images', $file, $relative, false, false);

		// If only path is required
		if ($path_only)
		{
			if (count($includes))
			{
				return $includes[0];
			}
			else
			{
				return null;
			}
		}
		else
		{
			return '<img src="' . (count($includes) ? $includes[0] : '') . '" alt="' . $alt . '" ' . $attribs . ' />';
		}
	}

	/**
	 * Write a <link rel="stylesheet" style="text/css" /> element
	 *
	 * @param   string   $file            path to file
	 * @param   array    $attribs         attributes to be added to the stylesheet
	 * @param   boolean  $relative        path to file is relative to /media folder
	 * @param   boolean  $path_only       return the path to the file only
	 * @param   boolean  $detect_browser  detect browser to include specific browser css files
	 *                                    will try to include file, file_*browser*, file_*browser*_*major*, file_*browser*_*major*_*minor*
	 *                                    <table>
	 *                                       <tr><th>Navigator</th>                  <th>browser</th>	<th>major.minor</th></tr>
	 *
	 *                                       <tr><td>Safari 3.0.x</td>               <td>konqueror</td>	<td>522.x</td></tr>
	 *                                       <tr><td>Safari 3.1.x and 3.2.x</td>     <td>konqueror</td>	<td>525.x</td></tr>
	 *                                       <tr><td>Safari 4.0 to 4.0.2</td>        <td>konqueror</td>	<td>530.x</td></tr>
	 *                                       <tr><td>Safari 4.0.3 to 4.0.4</td>      <td>konqueror</td>	<td>531.x</td></tr>
	 *                                       <tr><td>iOS 4.0 Safari</td>             <td>konqueror</td>	<td>532.x</td></tr>
	 *                                       <tr><td>Safari 5.0</td>                 <td>konqueror</td>	<td>533.x</td></tr>
	 *
	 *                                       <tr><td>Google Chrome 1.0</td>          <td>konqueror</td>	<td>528.x</td></tr>
	 *                                       <tr><td>Google Chrome 2.0</td>          <td>konqueror</td>	<td>530.x</td></tr>
	 *                                       <tr><td>Google Chrome 3.0 and 4.x</td>  <td>konqueror</td>	<td>532.x</td></tr>
	 *                                       <tr><td>Google Chrome 5.0</td>          <td>konqueror</td>	<td>533.x</td></tr>
	 *
	 *                                       <tr><td>Internet Explorer 5.5</td>      <td>msie</td>		<td>5.5</td></tr>
	 *                                       <tr><td>Internet Explorer 6.x</td>      <td>msie</td>		<td>6.x</td></tr>
	 *                                       <tr><td>Internet Explorer 7.x</td>      <td>msie</td>		<td>7.x</td></tr>
	 *                                       <tr><td>Internet Explorer 8.x</td>      <td>msie</td>		<td>8.x</td></tr>
	 *
	 *                                       <tr><td>Firefox</td>                    <td>mozilla</td>	<td>5.0</td></tr>
	 *                                    </table>
	 *                                    a lot of others
	 * @param   boolean  $detect_debug    detect debug to search for compressed files if debug is on
	 *
	 * @return  mixed  nothing if $path_only is false, null, path or array of path if specific css browser files were detected
	 *
	 * @see     JBrowser
	 * @since   11.1
	 */
	public static function stylesheet($file, $attribs = array(), $relative = false, $path_only = false, $detect_browser = true, $detect_debug = true)
	{
		// Need to adjust for the change in API from 1.5 to 1.6.
		// Function stylesheet($filename, $path = 'media/system/css/', $attribs = array())
		if (is_string($attribs))
		{
			JLog::add('The used parameter set in JHtml::stylesheet() is deprecated.', JLog::WARNING, 'deprecated');
			// Assume this was the old $path variable.
			$file = $attribs . $file;
		}

		if (is_array($relative))
		{
			// Assume this was the old $attribs variable.
			$attribs = $relative;
			$relative = false;
		}

		$includes = self::includeRelativeFiles('css', $file, $relative, $detect_browser, $detect_debug);

		// If only path is required
		if ($path_only)
		{
			if (count($includes) == 0)
			{
				return null;
			}
			elseif (count($includes) == 1)
			{
				return $includes[0];
			}
			else
			{
				return $includes;
			}
		}
		// If inclusion is required
		else
		{
			$document = JFactory::getDocument();
			foreach ($includes as $include)
			{
				$document->addStylesheet($include, 'text/css', null, $attribs);
			}
		}
	}

	/**
	 * Write a <script></script> element
	 *
	 * @param   string   $file            path to file
	 * @param   boolean  $framework       load the JS framework
	 * @param   boolean  $relative        path to file is relative to /media folder
	 * @param   boolean  $path_only       return the path to the file only
	 * @param   boolean  $detect_browser  detect browser to include specific browser js files
	 * @param   boolean  $detect_debug    detect debug to search for compressed files if debug is on
	 *
	 * @return  mixed  nothing if $path_only is false, null, path or array of path if specific js browser files were detected
	 *
	 * @see     JHtml::stylesheet
	 * @since   11.1
	 */
	public static function script($file, $framework = false, $relative = false, $path_only = false, $detect_browser = true, $detect_debug = true)
	{
		// Need to adjust for the change in API from 1.5 to 1.6.
		// function script($filename, $path = 'media/system/js/', $mootools = true)
		if (is_string($framework))
		{
			JLog::add('The used parameter set in JHtml::script() is deprecated.', JLog::WARNING, 'deprecated');
			// Assume this was the old $path variable.
			$file = $framework . $file;
			$framework = $relative;
		}

		// Include MooTools framework
		if ($framework)
		{
			JHtml::_('behavior.framework');
		}

		$includes = self::includeRelativeFiles('js', $file, $relative, $detect_browser, $detect_debug);

		// If only path is required
		if ($path_only)
		{
			if (count($includes) == 0)
			{
				return null;
			}
			elseif (count($includes) == 1)
			{
				return $includes[0];
			}
			else
			{
				return $includes;
			}
		}
		// If inclusion is required
		else
		{
			$document = JFactory::getDocument();
			foreach ($includes as $include)
			{
				$document->addScript($include);
			}
		}
	}

	/**
	 * Add the /media/system/js/core Javascript file.
	 *
	 * @param   boolean  $debug  True if debugging is enabled.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 * @deprecated  12.1  Use JHtml::_('behavior.framework'); instead.
	 */
	public static function core($debug = null)
	{
		JLog::add('JHtml::core() is deprecated. Use JHtml::_(\'behavior.framework\');.', JLog::WARNING, 'deprecated');
		JHtml::_('behavior.framework', false, $debug);
	}

	/**
	 * Set format related options.
	 *
	 * Updates the formatOptions array with all valid values in the passed
	 * array. See {@see JHtml::$formatOptions} for details.
	 *
	 * @param   array  $options  Option key/value pairs.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public static function setFormatOptions($options)
	{
		foreach ($options as $key => $val)
		{
			if (isset(self::$formatOptions[$key]))
			{
				self::$formatOptions[$key] = $val;
			}
		}
	}

	/**
	 * Returns formated date according to a given format and time zone.
	 *
	 * @param   string   $input      String in a format accepted by date(), defaults to "now".
	 * @param   string   $format     Format optional format for strftime
	 * @param   mixed    $tz         Time zone to be used for the date.  Special cases: boolean true for user
	 *                               setting, boolean false for server setting.
	 * @param   boolean  $gregorian  True to use Gregorian calenar
	 *
	 * @return  string    A date translated by the given format and time zone.
	 *
	 * @see     strftime
	 * @since   11.1
	 */
	public static function date($input = 'now', $format = null, $tz = true, $gregorian = false)
	{
		// Get some system objects.
		$config = JFactory::getConfig();
		$user = JFactory::getUser();

		// UTC date converted to user time zone.
		if ($tz === true)
		{
			// Get a date object based on UTC.
			$date = JFactory::getDate($input, 'UTC');

			// Set the correct time zone based on the user configuration.
			$date->setTimeZone(new DateTimeZone($user->getParam('timezone', $config->get('offset'))));
		}
		// UTC date converted to server time zone.
		elseif ($tz === false)
		{
			// Get a date object based on UTC.
			$date = JFactory::getDate($input, 'UTC');

			// Set the correct time zone based on the server configuration.
			$date->setTimeZone(new DateTimeZone($config->get('offset')));
		}
		// No date conversion.
		elseif ($tz === null)
		{
			$date = JFactory::getDate($input);
		}
		// UTC date converted to given time zone.
		else
		{
			// Get a date object based on UTC.
			$date = JFactory::getDate($input, 'UTC');

			// Set the correct time zone based on the server configuration.
			$date->setTimeZone(new DateTimeZone($tz));
		}

		// If no format is given use the default locale based format.
		if (!$format)
		{
			$format = JText::_('DATE_FORMAT_LC1');
		}
		// format is an existing language key
		elseif (JFactory::getLanguage()->hasKey($format))
		{
			$format = JText::_($format);
		}

		if ($gregorian)
		{
			return $date->format($format, true);
		}
		else
		{
			return $date->calendar($format, true);
		}
	}

	/**
	 * Creates a tooltip with an image as button
	 *
	 * @param   string  $tooltip  The tip string
	 * @param   mixed   $title    The title of the tooltip or an associative array with keys contained in
	 *                            {'title','image','text','href','alt'} and values corresponding to parameters of the same name.
	 * @param   string  $image    The image for the tip, if no text is provided
	 * @param   string  $text     The text for the tip
	 * @param   string  $href     An URL that will be used to create the link
	 * @param   string  $alt      The alt attribute for img tag
	 * @param   string  $class    CSS class for the tool tip
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	public static function tooltip($tooltip, $title = '', $image = 'tooltip.png', $text = '', $href = '', $alt = 'Tooltip', $class = 'hasTip')
	{
		if (is_array($title))
		{
			if (isset($title['image']))
			{
				$image = $title['image'];
			}
			if (isset($title['text']))
			{
				$text = $title['text'];
			}
			if (isset($title['href']))
			{
				$href = $title['href'];
			}
			if (isset($title['alt']))
			{
				$alt = $title['alt'];
			}
			if (isset($title['class']))
			{
				$class = $title['class'];
			}
			if (isset($title['title']))
			{
				$title = $title['title'];
			}
			else
			{
				$title = '';
			}
		}

		$tooltip = htmlspecialchars($tooltip, ENT_COMPAT, 'UTF-8');
		$title = htmlspecialchars($title, ENT_COMPAT, 'UTF-8');
		$alt = htmlspecialchars($alt, ENT_COMPAT, 'UTF-8');

		if (!$text)
		{
			$text = self::image($image, $alt, null, true);
		}

		if ($href)
		{
			$tip = '<a href="' . $href . '">' . $text . '</a>';
		}
		else
		{
			$tip = $text;
		}

		if ($title)
		{
			$tooltip = $title . '::' . $tooltip;
		}

		return '<span class="' . $class . '" title="' . $tooltip . '">' . $tip . '</span>';
	}

	/**
	 * Displays a calendar control field
	 *
	 * @param   string  $value    The date value
	 * @param   string  $name     The name of the text field
	 * @param   string  $id       The id of the text field
	 * @param   string  $format   The date format
	 * @param   array   $attribs  Additional HTML attributes
	 *
	 * @return  string  HTML markup for a calendar field
	 *
	 * @since   11.1
	 */
	public static function calendar($value, $name, $id, $format = '%Y-%m-%d', $attribs = null)
	{
		static $done;

		if ($done === null)
		{
			$done = array();
		}

		$readonly = isset($attribs['readonly']) && $attribs['readonly'] == 'readonly';
		$disabled = isset($attribs['disabled']) && $attribs['disabled'] == 'disabled';
		if (is_array($attribs))
		{
			$attribs = JArrayHelper::toString($attribs);
		}

		if (!$readonly && !$disabled)
		{
			// Load the calendar behavior
			self::_('behavior.calendar');
			self::_('behavior.tooltip');

			// Only display the triggers once for each control.
			if (!in_array($id, $done))
			{
				$document = JFactory::getDocument();
				$document
					->addScriptDeclaration(
					'window.addEvent(\'domready\', function() {Calendar.setup({
				// Id of the input field
				inputField: "' . $id . '",
				// Format of the input field
				ifFormat: "' . $format . '",
				// Trigger for the calendar (button ID)
				button: "' . $id . '_img",
				// Alignment (defaults to "Bl")
				align: "Tl",
				singleClick: true,
				firstDay: ' . JFactory::getLanguage()->getFirstDay() . '
				});});'
				);
				$done[] = $id;
			}
			return '<input type="text" title="' . (0 !== (int) $value ? self::_('date', $value, null, null) : '') . '" name="' . $name . '" id="' . $id
				. '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" ' . $attribs . ' />'
				. self::_('image', 'system/calendar.png', JText::_('JLIB_HTML_CALENDAR'), array('class' => 'calendar', 'id' => $id . '_img'), true);
		}
		else
		{
			return '<input type="text" title="' . (0 !== (int) $value ? self::_('date', $value, null, null) : '')
				. '" value="' . (0 !== (int) $value ? self::_('date', $value, 'Y-m-d H:i:s', null) : '') . '" ' . $attribs
				. ' /><input type="hidden" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" />';
		}
	}

	/**
	 * Add a directory where JHtml should search for helpers. You may
	 * either pass a string or an array of directories.
	 *
	 * @param   string  $path  A path to search.
	 *
	 * @return  array  An array with directory elements
	 *
	 * @since   11.1
	 */
	public static function addIncludePath($path = '')
	{
		// Force path to array
		settype($path, 'array');

		// Loop through the path directories
		foreach ($path as $dir)
		{
			if (!empty($dir) && !in_array($dir, JHtml::$includePaths))
			{
				jimport('joomla.filesystem.path');
				array_unshift(JHtml::$includePaths, JPath::clean($dir));
			}
		}

		return JHtml::$includePaths;
	}

    /* Custom Methods */

    /**
     * Receive and article object and try to get the intro image
     *
     *
     * @author  robert.reimi@gmail.com
     * @date    31/01/2013
     * @param   string  $item A joomla article object.
     * @param   string  $imgSrcAlt A relative image url to return if intro image cant be found.
     *
     * @return  string relative path to article intro image
     *
     * @since   11.1
     */
//     public static function articleImageIntro($item, $imgSrcAlt) {

//         $itemImages = json_decode($item->images);

//         if (!empty($itemImages->image_intro)) {

//             if (JHtml::startsWith($itemImages->image_intro, 'http')){
//                 return $itemImages->image_intro;
//             }
//             return JURI::base() . $itemImages->image_intro;
//         } else {
//             return JURI::base() . $imgSrcAlt;
//         }

//     }

    /**
     * Receive and article object and try to get the first link
     *
     *
     * @author  robert.reimi@gmail.com
     * @date    31/01/2013
     * @param   string  $item A joomla article object.
     * @param   string  $imgSrcAlt A relative image url to return if intro image cant be found.
     *
     * @return  string relative path to article intro image
     *
     * @since   11.1
     */
    public static function getItemLink($item) {

        $itemLinks = json_decode($item->urls);

        if ($itemLinks->urla != null){
            return $itemLinks->urla;
        }

        if ($itemLinks->urlb != null){
            return $itemLinks->urlb;
        }

        return false;
    }

    /**
     * Return true if the given string stars with given needle
     *
     *
     * @author  robert.reimi@gmail.com
     * @date    31/01/2013
     * @param   string  $haystack The string to check
     * @param   string  $needle The pattern
     *
     * @return  boolean true if the string stars with the $needle substring
     *
     * @since   11.1
     */
    private static function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    /**
     * Return true if the given string stars ends with needle
     *
     *
     * @author  robert.reimi@gmail.com
     * @date    31/01/2013
     * @param   string  $haystack The string to check
     * @param   string  $needle The pattern
     *
     * @return  boolean true if the string ends with the $needle substring
     *
     * @since   11.1
     */
    private static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     *
     * Get the first element between given tags
     *
     * @param   string  $content  Content to be searched.
     * @param   string  $tags     Tags to be included.
     * @return  string  first tag found
     *
     * @since   11.1
     * @author	Robert Pérez Reimi
     */
    public static function getFirstTagItem($content, $tags)
    {

        $pos = -1;
        $tag = "";

        foreach ($tags as $currentTag){
            $currentPos = strpos($content , "<".$currentTag);
            if ($currentPos != false){
                if ($pos == -1){
                    $pos = $currentPos;
                    $tag = $currentTag;
                } else {
                    if ($currentPos < $pos){
                        $pos = $currentPos;
                        $tag = $currentTag;
                    }
                }
            }
        }

        if ($tag == ""){
            return false;
        } else {
            $dom_doc = new DOMDocument();
            $dom_doc->loadHTML($content);
            $tags_b = $dom_doc->getElementsByTagName($tag);
            $element = new stdClass;
            $element->type = $tags_b->item(0)->nodeName;
            $element->src = $tags_b->item(0)->attributes->getNamedItem("src")->nodeValue;
            return $element;
        }
    }

    /*
     * Returns youtube video id, none if youtube url is invalid
     */
    public static function getYoutubeVideoId($url)
    {
    	$code = '';
    	
    	parse_str( parse_url( $url, PHP_URL_QUERY ), $result );
    	
    	if (isset($result['v'])) {
        	$code = $result['v'];
    	}
        

        // If we have no match, need to check for youtu.be address
        // which is the new youtbe share address
        if( empty($code) )
        {
            $pattern    = "'youtu.be/([A-Za-z0-9-_]+)'s";
            preg_match($pattern, $url, $matches);

            if($matches && !empty($matches[1]) ){
                $code = $matches[1];
            }
        }
        
        return $code;
    }

    /*
     * Returns youtube video preview image url in 120 x 90 format jpg
     *
     */
    public static function getYoutubeThumbnail($videoId) {
        return 'http://img.youtube.com/vi/' . $videoId . '/default.jpg';
    }


    /*
	* Generate youtube html link, for modal support
	*
	*/
    public static function generateYoutubeLink($videoId, $class = '', $rel = '') {
        $html = '<a rel="' . $rel . '" style="display:block;"  href="http://www.youtube.com/watch?v='. $videoId . '">';
        $html .= '<img src="' . JHtml::getYoutubeThumbnail($videoId) . '" class="' . $class . '"/>';
        $html .= '<div class="btn_play"></div>';
        $html .= '</a>';
        return $html;
    }

    /*
	* Generate youtube iframe with lightbox support
	*
	*/
    public static function generateYoutubeIframe($url, $width, $height, $class, $rel){
        $videoId = self::getYoutubeVideoId($url);
        $html = '';
        if ($videoId != '') {
            $html = '<div align="center" class="'. $class .'" style="position:relative; width:' . $width .'px; height:' . $height .'px;">';
            $html .= '<iframe id="youtube-player" type="text/html" width="'. ($width-10) . 'px" height="'. ($height-10) . 'px" src="http://www.youtube.com/embed/' . $videoId . '?enablejsapi=1&theme=light&wmode=transparent" frameborder="0" allowfullscreen></iframe>';
            $html .= '<a href="http://www.youtube.com/watch?v=' . $videoId . '" style="display:block; position:absolute; left:5px; top:5px;" rel="' . $rel . '"><div style="width: ' . $width . 'px; height: ' . $height . 'px;"></div></a>';
            $html .= '</div>';
        }

        return $html;
    }
    
    
    /**
     *
     * Get intro image or full text image, and its alternative text, if they exist
     *
     * @param   string  $article  Article to be searched
     * @return  string  first image and alt text found
     *
     * @since   11.1
     * @author	Andrea Lebrun
     */
    public static function articleImageIntro ($article) {
    	$articleImages = json_decode($article->images);
    	$imageData = new stdClass;
    	if (!empty($articleImages->image_intro)) {
    		$imageData->source = $articleImages->image_intro;
    		$imageData->alt = $articleImages->image_intro_alt;
    		return $imageData;
    	} 
    	if (!empty($articleImages->image_fulltext)) {
    		$imageData->source = $articleImages->image_fulltext;
    		$imageData->alt = $articleImages->image_fulltext_alt;
    		return $imageData;
    	}
	    return false;
    }
    

    /**
     *
     * Get article thumb image or video html tag
     *
     * @param   string  $article  Article to be searched.
     * @return  string  $imgSrcAlt A image url to return if intro image cant be found
     * @return  string  $imageClass class name to image specification
     * @return  string  $videoClass class name to video specification
     * @return  string  $rel rel name to video specification
     *
     * @since   11.1
     * @author	Andrea Lebrun
     */
    public static function articleThumb($article, $imgSrcAlt, $imageClass ='', $rel ='') {
    	if ($videoLink = self::getItemLink($article)) {
    		$videoId = self::getYoutubeVideoId($videoLink);
    		if (!empty($videoId)) {
    			return self::generateYoutubeLink($videoId, $imageClass, $rel);
    		}
    	}
    	if ($item = self::articleImageIntro($article)) {
    		return self::image($item->source, $item->alt, array('class' => $imageClass));
    	}	
    	if (!(JHtml::startsWith($imgSrcAlt, 'http'))){
    		$imgSrcAlt = JURI::base() . $imgSrcAlt;
    	}
    	return self::image($imgSrcAlt, '', array('class' => $imageClass));
    }

    /* Converts 24 hour format to 12 hour am/pm format */
    public static function formatHora($hora) {
        return date("g a",strtotime("$hora:00:00"));
    }

}
