<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$parameters = array(
	'config-file:' => 'C:',
	'silent' => 's',
	'help' => 'h',
	'version' => 'v',
	'imap' => 'i',
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
	$long = str_replace(':', '', $long);
	if (isset($short)) {
		$short = str_replace(':', '', $short);
	}
	$long_to_shorts[$long] = $short;
}

$options = getopt(
	implode(
		'',
		array_filter(
			array_values($parameters),
			function ($value) {
				return isset($value);
			}
		)
	),
	array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
	return isset($value);
})) as $short => $long) {
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}
}

if (array_key_exists('version', $options)) {
	print <<<EOF
lms-pdf-imap-parser.php - (C) 2001-2022 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-pdf-imap-parser.php - (C) 2001-2022 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-s, --silent                    suppress any output, except errors;
-i, --imap                      IMAP script mode,

EOF;
	exit(0);
}

$quiet = array_key_exists('silent', $options);
if (!$quiet) {
	print <<<EOF
lms-rtparser.php - (C) 2001-2020 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
	$CONFIG_FILE = $options['config-file'];
} else {
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
	die("Unable to read configuration file [" . $CONFIG_FILE . "]!" . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['storage_dir'] = (!isset($CONFIG['directories']['storage_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'storage' : $CONFIG['directories']['storage_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('STORAGE_DIR', $CONFIG['directories']['storage_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');
require_once(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPurchasesPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR
	. 'lib' . DIRECTORY_SEPARATOR . 'PURCHASES.class.php');

if (ConfigHelper::checkConfig('phpui.logging') && class_exists('SYSLOG')) {
    $SYSLOG = new SYSLOG($DB);
} else {
    $SYSLOG = null;
}

$AUTH = null;

$LMS = new LMS($DB, $AUTH, $SYSLOG);

$rtparser_server = ConfigHelper::getConfig(
	'rt.imap_server',
	isset($smtp_options['host']) ? $smtp_options['host'] : ConfigHelper::GetConfig('mail.smtp_host')
);

$rtparser_username = ConfigHelper::getConfig('rt.imap_username', 'username_not_set1');
$rtparser_password = ConfigHelper::getConfig('rt.imap_password', 'password_not_set1');
$rtparser_use_seen_flag = ConfigHelper::getConfig('rt.imap_use_seen_flag', true);
$rtparser_folder = ConfigHelper::getConfig('rt.imap_folder', 'INBOX');

define('MODE_FILE', 1);
define('MODE_IMAP', 2);

$mode = isset($options['imap']) ? MODE_IMAP : MODE_FILE;

if (!function_exists('mailparse_msg_create')) {
	fprintf($stderr, "Fatal error: PECL mailparse module is required!" . PHP_EOL);
	exit(2);
}

$postid = null;

if ($mode == MODE_IMAP) {
	if (!function_exists('imap_open')) {
		fprintf($stderr, "Fatal error: PHP IMAP extension is required!" . PGP_EOL);
		exit(5);
	}

	if (empty($rtparser_server) || empty($rtparser_username) || empty($rtparser_password)) {
		fprintf($stderr, "Fatal error: mailbox credentials are not set!" . PHP_EOL);
		exit(6);
	}

	$ih = @imap_open("{" . $rtparser_server . "}" . $rtparser_folder, $rtparser_username, $rtparser_password);
	if (!$ih) {
		fprintf($stderr, 'Cannot connect to mail server: ' . imap_last_error() . '!' . PHP_EOL);
		exit(7);
	}

	$posts = imap_search($ih, $rtparser_use_seen_flag ? 'UNSEEN' : 'ALL');
	if (empty($posts)) {
		imap_close($ih);
		die;
	}

	$postid = reset($posts);
} else {
	if (isset($options['message-file'])) {
		if (!is_readable($options['message-file'])) {
			die('Cannot read message file \'' . $options['message-file'] . '\'!' . PHP_EOL);
		}
		$buffer = file_get_contents($options['message-file']);
	} else {
		$buffer = file_get_contents('php://stdin');
	}
}

while (isset($buffer) || ($postid !== false && $postid !== null)) {
	if ($postid !== false && $postid !== null) {
		$buffer = imap_fetchbody($ih, $postid, '');

		if ($rtparser_use_seen_flag) {
			imap_setflag_full($ih, $postid, "\\Seen");
		} else {
			imap_clearflag_full($ih, $postid, "\\Seen");
		}
	}

	if (!empty($buffer)) {
		if (!preg_match('/\r?\n$/', $buffer)) {
			$buffer .= "\n";
		}

		$mail = mailparse_msg_create();
		if ($mail === false) {
			fprintf($stderr, "Fatal error: mailparse_msg_create() error!" . PHP_EOL);
			exit(3);
		}

		if (mailparse_msg_parse($mail, $buffer) === false) {
			fprintf($stderr, "Fatal error: mailparse_msg_parse() error!" . PHP_EOL);
			exit(4);
		}

		$parts = mailparse_msg_get_structure($mail);
		$partid = array_shift($parts);
		$part = mailparse_msg_get_part($mail, $partid);
		$partdata = mailparse_msg_get_part_data($part);
		$headers = $partdata['headers'];

		$mh_from = iconv_mime_decode($headers['from']);
		$mh_to = iconv_mime_decode($headers['to']);
		$mh_cc = isset($headers['cc']) ? iconv_mime_decode($headers['cc']) : '';
		$mh_msgid = iconv_mime_decode($headers['message-id']);
		$mh_replyto = isset($headers['reply-to']) ? iconv_mime_decode($headers['reply-to']) : '';
		$mh_subject = isset($headers['subject']) ? iconv_mime_decode($headers['subject']) : '';
		if (!strlen($mh_subject)) {
			$mh_subject = trans('(no subject)');
		}
		$mh_references = iconv_mime_decode($headers['references']);
		$files = array();
		$attachments = array();

		$mail_headers = substr($buffer, $partdata['starting-pos'], $partdata['starting-pos-body'] - $partdata['starting-pos'] - 1);
		$decoded_mail_headers = array();
		foreach (explode("\n", $mail_headers) as $mail_header) {
			$decoded_mail_header = @iconv_mime_decode($mail_header);
			if ($decoded_mail_header === false) {
				$decoded_mail_headers[] = $mail_header;
			} else {
				$decoded_mail_headers[] = $decoded_mail_header;
			}
		}
		$mail_headers = implode("\n", $decoded_mail_headers);
		unset($decoded_mail_headers);

		if (preg_match('#multipart/#', $partdata['content-type']) && !empty($parts)) {
			$mail_body = '';
			while (!empty($parts)) {
				$partid = array_shift($parts);
				$part = mailparse_msg_get_part($mail, $partid);
				$partdata = mailparse_msg_get_part_data($part);
				$html = strpos($partdata['content-type'], 'html') !== false;
				$isAttachment = isset($partdata['content-disposition']) && $partdata['content-disposition'] == 'attachment';
				if (!$isAttachment
					&& preg_match('/text/', $partdata['content-type'])
					&& ($mail_body == '' || ($html && $prefer_html) || (!$html && !$use_html))) {
					$mail_body = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);
					$charset = $partdata['content-charset'];
					$transfer_encoding = isset($partdata['transfer-encoding']) ? $partdata['transfer-encoding'] : '';
					switch ($transfer_encoding) {
						case 'base64':
							$mail_body = base64_decode($mail_body);
							break;
						case 'quoted-printable':
							$mail_body = quoted_printable_decode($mail_body);
							break;
					}
					$mail_body = iconv($charset, 'UTF-8', $mail_body);

					$contenttype = 'text/plain';

					if ($partdata['content-type'] == 'text/html') {
						if ($use_html) {
							$contenttype = 'text/html';
							$mail_body = $hm_purifier->purify($mail_body);
						} else {
							$html2text = new \Html2Text\Html2Text($mail_body, array());
							$mail_body = $html2text->getText();
						}
					}
				} elseif (preg_match('#multipart/alternative#', $partdata['content-type']) && $mail_body == '') {
					while (!empty($parts) && strpos($parts[0], $partid . '.') === 0) {
						$subpartid = array_shift($parts);
						$subpart = mailparse_msg_get_part($mail, $subpartid);
						$subpartdata = mailparse_msg_get_part_data($subpart);
						$html = strpos($subpartdata['content-type'], 'html') !== false;
						if (preg_match('/text/', $subpartdata['content-type'])
							&& (trim($mail_body) == '' || ($html && $prefer_html) || (!$html && !$use_html))) {
							$mail_body = substr($buffer, $subpartdata['starting-pos-body'], $subpartdata['ending-pos-body'] - $subpartdata['starting-pos-body']);
							$charset = $subpartdata['content-charset'];
							$transfer_encoding = isset($subpartdata['transfer-encoding']) ? $subpartdata['transfer-encoding'] : '';
							switch ($transfer_encoding) {
								case 'base64':
									$mail_body = base64_decode($mail_body);
									break;
								case 'quoted-printable':
									$mail_body = quoted_printable_decode($mail_body);
									break;
							}
							$mail_body = iconv($charset, 'UTF-8', $mail_body);

							$contenttype = 'text/plain';

							if ($subpartdata['content-type'] == 'text/html') {
								if ($use_html) {
									$contenttype = 'text/html';
									$mail_body = $hm_purifier->purify($mail_body);
								} else {
									$html2text = new \Html2Text\Html2Text($mail_body, array());
									$mail_body = $html2text->getText();
								}
							}
						}
					}
				} elseif ((isset($partdata['content-disposition']) && ($isAttachment
							|| $partdata['content-disposition'] == 'inline')) || isset($partdata['content-id'])) {
					$file_content = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);
					$transfer_encoding = isset($partdata['transfer-encoding']) ? $partdata['transfer-encoding'] : '';
					switch ($transfer_encoding) {
						case 'base64':
							$file_content = base64_decode($file_content);
							break;
						case 'quoted-printable':
							$file_content = quoted_printable_decode($file_content);
							break;
					}
					$file_name = isset($partdata['content-name']) ? $partdata['content-name'] :
						(isset($partdata['disposition-filename']) ? $partdata['disposition-filename'] : '');
					if (!$file_name) {
						unset($file_content);
						continue;
					}
					$file_name = iconv_mime_decode($file_name);

					if (!isset($partdata['content-id']) && $image_max_size && class_exists('Imagick') && strpos($partdata['content-type'], 'image/') === 0) {
						$imagick = new \Imagick();
						$imagick->readImageBlob($file_content);
						$width = $imagick->getImageWidth();
						$height = $imagick->getImageHeight();
						if ($height > $width) {
							if ($height > $image_max_size) {
								$imagick->scaleImage(0, $image_max_size);
								$file_content = $imagick->getImageBlob();
							}
						} else {
							if ($width > $image_max_size) {
								$imagick->scaleImage($image_max_size, 0);
								$file_content = $imagick->getImageBlob();
							}
						}
					}

					$files[] = array(
						'name' => $file_name,
						'type' => $partdata['content-type'],
						'content' => &$file_content,
						'content-id' => !$isAttachment && isset($partdata['content-id']) ? $partdata['content-id'] : null,
					);
					$attachments[] = array(
						'content_type' => $partdata['content-type'],
						'filename' => $file_name,
						'data' => &$file_content,
						'content-id' => !$isAttachment && isset($partdata['content-id']) ? $partdata['content-id'] : null,
					);
					unset($file_content);
				}
			}
		} else {
			$charset = $partdata['content-charset'];
			$mail_body = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);

			$transfer_encoding = isset($partdata['transfer-encoding']) ? $partdata['transfer-encoding'] : '';
			switch ($transfer_encoding) {
				case 'base64':
					$mail_body = base64_decode($mail_body);
					break;
				case 'quoted-printable':
					$mail_body = quoted_printable_decode($mail_body);
					break;
			}

			$mail_body = iconv($charset, 'UTF-8', $mail_body);

			$contenttype = 'text/plain';

			if ($partdata['content-type'] == 'text/html') {
				if ($use_html) {
					$contenttype = 'text/html';
					$mail_body = $hm_purifier->purify($mail_body);
				} else {
					$html2text = new \Html2Text\Html2Text($mail_body, array());
					$mail_body = $html2text->getText();
				}
			}
		}

		mailparse_msg_free($mail);

		if ($contenttype != 'text/html') {
			if (!empty($files)) {
				foreach ($files as &$file) {
					unset($file['content-id']);
				}
				unset($file);
				foreach ($attachments as &$attachment) {
					unset($attachment['content-id']);
				}
				unset($attachment);
			}
		}

		$timestamp = time();

		$params = array(
			'requestor' => empty($fromname) ? $mh_from : $fromname,
			'requestor_mail' => empty($fromemail) ? null : $fromemail,
			'subject' => $mh_subject,
			'createtime' => $timestamp,
			'mailfrom' => $mh_from,
			'comment' => htmlspecialchars(strip_tags($mail_body)),
			'files' => $files,
		);

		$PURCHASES1 = new PURCHASES;

		////$PURCHASES1->AddPurchaseFiles($params);
	}
}