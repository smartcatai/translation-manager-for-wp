<?php
///**
// * Created by PhpStorm.
// * User: Diversant_
// * Date: 01.09.2017
// * Time: 22:14
// */
//

$resDir = __DIR__ . '/../../translation-connectors-svn';

if (!is_dir("$resDir/trunk") || !is_dir("$resDir/tags")) {
    die("SVN dir not found");
}

function rrmdir( $path, $t = "1" ) {
	$rtrn = "1";
	if ( file_exists( $path ) && is_dir( $path ) ) {
		$dirHandle = opendir( $path );
		while ( false !== ( $file = readdir( $dirHandle ) ) ) {
			if ( $file != '.' && $file != '..' ) {
				$tmpPath = $path . '/' . $file;
				chmod( $tmpPath, 0777 );
				if ( is_dir( $tmpPath ) ) {
					rrmdir( $tmpPath );
				} else {
					if ( file_exists( $tmpPath ) ) {
						unlink( $tmpPath );
					}
				}
			}
		}
		closedir( $dirHandle );
		if ( $t == "1" ) {
			if ( file_exists( $path ) ) {
				rmdir( $path );
			}
		}
	} else {
		$rtrn = "0";
	}

	return $rtrn;
}

function rcopy( $src, $dst ) {
	if ( file_exists( $dst ) ) {
		rrmdir( $dst );
	}
	if ( is_dir( $src ) ) {
		if ( ! file_exists( $dst ) ) {
			mkdir( $dst, 0777, true );
		}
		$files = scandir( $src );
		foreach ( $files as $file ) {
			if ( $file != "." && $file != ".." ) {
				rcopy( "$src/$file", "$dst/$file" );
			}
		}
	} else if ( file_exists( $src ) ) {
		copy( $src, $dst );
	}
}

function copyFiles( $sourceDir, $destDir ) {
	$dir_iterator = new RecursiveDirectoryIterator( $sourceDir );
	$iterator     = new RecursiveIteratorIterator( $dir_iterator, RecursiveIteratorIterator::SELF_FIRST );

	foreach ( $iterator as $file ) {
		$pathInfo = pathinfo( $file );
		if ( $pathInfo['extension'] == 'php' ) {
			copy( $file->getRealPath(), $destDir . '/' . $pathInfo['basename'] );
		}
	}
}

function fileReplace( $search, $replace, $file ) {
	$content = file_get_contents( $file );
	$content = str_replace( $search, $replace, $content );
	file_put_contents( $file, $content );

}

//Получаем версию из readme.txt
$readme = file_get_contents( __DIR__ . '/../readme.txt' );
$re     = '/Stable tag:\s*(\d+\.\d+\.\d+)\s*\n/';
preg_match( $re, $readme, $matches );
$readmeVersion = $matches[1] ?? 'readme';

//Получаем версию из translation-connectors.php
$plugin = file_get_contents( __DIR__ . '/../translation-connectors.php' );
$re     = '/Version:\s*(\d+\.\d+\.\d+)\s*\n/';
preg_match( $re, $plugin, $matches );
$pluginVersion = $matches[1] ?? 'plugin';

if ( $readmeVersion !== $pluginVersion ) {
	die ( "Версия плагина в readme.txt $readmeVersion и translation-connectors.php $pluginVersion не совпадают" );
}

$version = $readmeVersion;

//Проверяем присутсвие Changelog для текущей версии
$re = '/= (.+) =/';
preg_match_all( $re, $readme, $matches, PREG_SET_ORDER, 0 );

$find = false;
foreach ( $matches as $match ) {
	if ((strpos($match[1], "$version ") !==false) && (strlen($version) <= strlen($match[1]))) {
		$find = true;
		break;
	}
}

if ( !$find ) {
	die ( "readme.txt не содержит Changelog для версии $version" );
}

$message = readline( "Введите описание коммита: " );

rcopy( __DIR__ . '/../assets', "$resDir/assets" );

$copyDirs = [
	'css',
	'images',
	'inc',
	'js',
	'languages',
	'views',
];

$copyFiles = [
	'index.php',
	'LICENSE.txt',
	'readme.txt',
	'translation-connectors.php',
	'uninstall.php',
];

$rmDirs = [
	'inc/vendor/symfony/polyfill-php70/Resources/stubs',
	'inc/vendor/symfony/dependency-injection/.git',
	'inc/vendor/symfony/dependency-injection/Tests',
	'inc/vendor/symfony/config/.git',
	'inc/vendor/symfony/config/Tests',
	'inc/vendor/symfony/filesystem/Tests',
	'inc/vendor/symfony/property-access/Tests',
	'inc/vendor/symfony/inflector/Tests',
	'inc/vendor/symfony/serializer/Tests',
	'inc/vendor/symfony/console/Tests',
	'inc/vendor/symfony/debug/Tests',
	'inc/vendor/symfony/yaml/Tests',
	'inc/vendor/symfony/options-resolver/Tests',
	'inc/vendor/symfony/debug/Resources/ext/tests',
	'inc/vendor/jane/jane/tests',
	'inc/vendor/jane/open-api/tests',
	'inc/vendor/jane/openapi-runtime/tests',
	'inc/vendor/jane/runtime/tests',
	'inc/vendor/clue/stream-filter/tests',
];

chdir( __DIR__ . "/.." );
//exec('composer update --no-dev');

if ( ! file_exists( "$resDir/trunk" ) ) {
	mkdir( "$resDir/trunk", 0777, true );
}

chdir( "$resDir" );

foreach ( $copyFiles as $file ) {
	copy( __DIR__ . "/../$file", "$resDir/trunk/$file" );
}

foreach ( $copyDirs as $dir ) {
	rcopy( __DIR__ . "/../$dir", "$resDir/trunk/$dir" );
}

foreach ( $rmDirs as $dir ) {
	exec( "svn rm " . "$resDir/trunk/$dir");
	rrmdir( "$resDir/trunk/$dir" );
}


exec( "svn commit -m \"$message\"" );

if ( ! file_exists( "$resDir/tags/$version" ) ) {
	mkdir( "$resDir/tags/$version", 0777, true );
}

exec( "svn --force --depth infinity add ." );
exec( "svn cp trunk/* tags/$version" );
exec( "svn commit  -m \"$message\"" );