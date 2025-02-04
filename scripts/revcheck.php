#!/usr/bin/php -q
<?php
/*
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2023 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | https://www.php.net/license/3_01.txt.                                |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net, so we can mail you a copy immediately.              |
  +----------------------------------------------------------------------+
  | Authors:    Thomas Schoefbeck <tom@php.net>                          |
  |             Gabor Hojtsy <goba@php.net>                              |
  |             Mark Kronsbein <mk@php.net>                              |
  |             Jan Fabry <cheezy@php.net>                               |
  |             André L F S Bacci <ae@php.net>                           |
  +----------------------------------------------------------------------+
*/

require_once __DIR__ . '/translation/lib/all.php';

if ( $argc != 2 )
{
    print <<<USAGE

  Check the revision of translated files against the actual english XML files
  and print statistics.

  Usage:
    {$argv[0]} [translation]

  [translation] must be a valid git checkout directory of a translation.

  Read more about revision comments and related functionality in the
  PHP Documentation Howto: https://doc.php.net/guide/


USAGE;
    exit;
}

$lang = $argv[1];
$revc = new RevcheckRun( 'en' , $argv[1] );
$data = $revc->revData;

print_html_all( $data );

// Output

function print_html_all( RevcheckData $data )
{
    print_html_header( $data );
    print_html_translators( $data );
    print_html_oldwip( $data );
    print_html_notinen( $data );
    print_html_revtag( $data );
    print_html_untranslated( $data );
    print_html_footer();
}

function print_html_header( RevcheckData $data )
{
    $lang = $data->lang;
    $date = $data->date;
    print <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<style type="text/css">
body { margin:0px 0px 0px 0px; background-color:#F0F0F0; font-family: sans-serif; text-align: center; }
a { color: black; }
h1 { color: #FFFFFF; }
table { margin-left: auto; margin-right: auto; text-align: left; border-spacing: 2px; }
th { color: white; background-color: #666699; padding: 0.2em; text-align: center; vertical-align: middle; }
td { padding: 0.2em 0.3em; }
.copy { margin:0; padding: 0; font-size:small; }
.copy:hover { text-transform: uppercase; }
.copy:active { background: aqua; font-weight: bold; }
.b { font-weight: bold; }
.c { text-align: center; }
.o { white-space: nowrap; overflow: hidden; max-width: 5em; }
.oc { white-space: nowrap; overflow: hidden; max-width: 7em; }
.bggray { background-color: #dcdcdc;}
.bgorange { background-color: #f4a460;}
</style>
</head>
<body>

<div id="header" style="background-color: #9999CC;">
<h1 style="margin: 0; padding: 0.5em;">Status of the PHP Manual $lang translation</h1>
<p style="margin: 0; padding: 0 1em 1em 1em;">Generated: $date</p>
</div>
HTML;
}

function print_html_menu( string $href )
{
    print <<<HTML
<a id="$href"/>
<p><a href="#intro">Introduction</a>
| <a href="#translators">Translators</a>
| <a href="#filesummary">File summary</a>
| <a href="#files">Outdated Files</a>
| <a href="#notinen">Not in EN tree</a>
| <a href="#revtag">Missing or invalid revtag</a>
| <a href="#untranslated">Untranslated files</a>
</p><p/>
HTML;
}

function print_html_translators( RevcheckData $data )
{
    $translators = $data->translators;
    if ( count( $translators ) == 0 )
        return;

    print_html_menu("intro");
    print <<<HTML
<table class="c">
 <tr><td>{$data->intro}</td></tr>
</table>
<p/>
<table class="c">
  <tr>
    <th rowspan=2>Translator's name</th>
    <th rowspan=2>Contact email</th>
    <th rowspan=2>Nick</th>
    <th rowspan=2>V<br>C<br>S</th>
    <th colspan=4>Files maintained</th>
  </tr>
  <tr>
    <th>ok</th>
    <th>old</th>
    <th>misc</th>
    <th>sum</th>
  </tr>
HTML;

    foreach( $translators as $person )
    {
        // Unknown or untracked on translations.xml
        if ( $person->name == "" && $person->email == "" && $person->vcs == "" )
            continue;

        $personSum = $person->countOk + $person->countOld + $person->countOther;

        print <<<HTML
<tr>
  <td>{$person->name}</td>
  <td>{$person->email}</td>
  <td>{$person->nick}</td>
  <td class=c>{$person->vcs}</td>
  <td class=c>{$person->countOk}</td>
  <td class=c>{$person->countOld}</td>
  <td class=c>{$person->countOther}</td>
  <td class=c>{$personSum}</td>
</tr>
HTML;
    }
    print "</table>\n";

    print_html_menu("filesummary");
    print <<<HTML
<table class="c">
<tr>
  <th>File status type</th>
  <th>Number of files</th>
  <th>Percent of files</th>
</tr>
HTML;

    $filesTotal = 0;
    foreach ( $data->fileSummary as $count )
        $filesTotal += $count;

    foreach( RevcheckStatus::cases() as $key )
    {
        $label = "";
        $count = $data->fileSummary[ $key->value ];
        $perc = number_format( $count / $filesTotal * 100 , 2 ) . "%";
        switch( $key )
        {
            case RevcheckStatus::TranslatedOk:  $label = "Up to date files"; break;
            case RevcheckStatus::TranslatedOld: $label = "Outdated files"; break;
            case RevcheckStatus::TranslatedWip: $label = "Work in progress"; break;
            case RevcheckStatus::RevTagProblem: $label = "Revision tag missing/problem"; break;
            case RevcheckStatus::NotInEnTree:   $label = "Not in EN tree"; break;
            case RevcheckStatus::Untranslated:  $label = "Available for translation"; break;
        }

        print <<<HTML
<tr>
  <td>$label</td>
  <td>$count</td>
  <td>$perc</td>
</tr>
HTML;
    }

    print <<<HTML
<tr>
  <td><b>Files total</b></td>
  <td><b>$filesTotal</b></td>
  <td><b>100%</b></td>
</tr>
</table>
HTML;
}

function print_html_oldwip( RevcheckData $data )
{
    print_html_menu("files");

    $total =  $data->fileSummary[ RevcheckStatus::TranslatedOld->value ];
    $total += $data->fileSummary[ RevcheckStatus::TranslatedWip->value ];
    if ( $total == 0 )
    {
        print "<p>Hooray! There is no files to update, nice work!</p>\n\n";
        return;
    }

    print <<<HTML
<table>
 <tr>
  <th rowspan="2">Translated file</th>
  <th rowspan="2">Changes</th>
  <th colspan="2">Hash</th>
  <th rowspan="2">Maintainer</th>
  <th rowspan="2">Status</th>
  <th rowspan="2">Days</th>
 </tr>
 <tr>
  <th>en</th>
  <th>{$data->lang}</th>
 </tr>\n
HTML;

    $now = new DateTime( 'now' );
    $path = null;

    foreach( $data->fileDetail as $key => $file )
    {
        switch ( $file->status )
        {
            case RevcheckStatus::TranslatedOld:
            case RevcheckStatus::TranslatedWip:
                break;
            default:
                continue 2;
        }

        if ( $path !== $file->path )
        {
            $path = $file->path;
            $path2 = $path == '' ? '/' : $path;
            print " <tr><th colspan='7' class='c'>$path2</th></tr>";
        }

        $ma = $file->maintainer;
        $st = $file->completion;
        $ll = strtolower( $data->lang );
        $kh = hash( 'sha256' , $key );
        $d1 = "https://doc.php.net/revcheck.php?p=plain&amp;lang={$ll}&amp;hbp={$file->hashRvtg}&amp;f=$key";
        $d2 = "https://doc.php.net/revcheck.php?p=plain&amp;lang={$ll}&amp;hbp={$file->hashRvtg}&amp;f=$key&amp;c=on";

        $nm = "<a href='$d1'>{$file->name}</a> <a href='$d2'>[colored]</a>";
        $h1 = "<a href='https://github.com/php/doc-en/blob/{$file->hashLast}/$key'>{$file->hashLast}</a>";
        $h2 = "<a href='https://github.com/php/doc-en/blob/{$file->hashRvtg}/$key'>{$file->hashRvtg}</a>";

        if ( $file->adds > 0 || $file->dels > 0 )
            $ch = "<span style='color: darkgreen;'>+{$file->adds}</span> <span style='color: firebrick;'>-{$file->dels}</span>";
        else
            $ch = "<span></span>";

        $bgdays = '';
        if ( $file->days > 90 )
            $bgdays = 'bgorange';

        print <<<HTML
 <tr class="bggray">
  <td>$nm</td>
  <td class="c">$ch</td>
  <td class="oc">
    <button class="btn copy" data-clipboard-text="{$file->hashLast}">Copy</button> $h1
  </td>
  <td class="o">$h2</td>
  <td class="c">$ma</td>
  <td class="c">$st</td>
  <td class="c {$bgdays}">{$file->days}</td>
 </tr>\n
HTML;
    }

    print "</table><p/>\n\n";
}

function print_html_notinen( RevcheckData $data )
{
    print_html_menu("notinen");

    if ( $data->fileSummary[ RevcheckStatus::NotInEnTree->value ] == 0 )
    {
        print "<p>Good, it seems that this translation doesn't contain any file which is not present in source tree.</p>\n\n";
        return;
    }

    print <<<HTML
<table class="c">
 <tr>
  <th> Files which is not present in source tree </th>
  <th> Size kB </th>
 </tr>
HTML;
    $header = null;
    foreach ( $data->fileDetail as $file )
    {
        if ( $file->status != RevcheckStatus::NotInEnTree )
            continue;

        if ( $header !== $file->path )
        {
            $header = $file->path;
            print " <tr><th colspan='2'>$header</th></tr>";
        }

        $name = $file->name;
        $size = round( $file->size / 1024 );

        print <<<HTML
 <tr class=bggray>
  <td class="c">{$name}</td>
  <td class="c">{$size}</td>
 </tr>
HTML;
    }

    print "</table><p/>\n\n";
}

function print_html_revtag( RevcheckData $data )
{
    print_html_menu("revtag");
    if ( $data->fileSummary[ RevcheckStatus::RevTagProblem->value ] == 0 )
    {
        echo "<p>Good, all files contain valid revtags.</p>\n\n";
        return;
    }

    echo <<<HTML
<table class="c">
<tr>
 <th> Files with invalid or missing revision tags </th>
 <th> Size kB </th>
</tr>
HTML;

    $last_path = null;
    foreach ( $data->fileDetail as $file )
    {
        if ( $file->status != RevcheckStatus::RevTagProblem )
            continue;

        if ( $last_path != $file->path )
        {
            $path = $file->path == '' ? '/' : $file->path;
            echo "<tr><th colspan='2'>$path</th></tr>";
            $last_path = $file->path;
        }
        $size = round( $file->size / 1024 );
        echo "<tr class='bgorange'><td>{$file->name}</td><td>{$size}</td></tr>";
    }
    echo '</table>';
}

function print_html_untranslated( RevcheckData $data )
{
    print_html_menu("untranslated");
    if ( $data->fileSummary[ RevcheckStatus::Untranslated->value ] == 0 )
    {
        echo "<p>No file left untranslated!</p>\n\n";
        return;
    }

    print <<<HTML
<table class="c">
 <tr>
  <th>Untranslated files</th>
  <th>Last hash</th>
  <th>kb</th>
 </tr>
HTML;

    $path = null;
    foreach ( $data->fileDetail as $key => $file )
    {
        if ( $file->status != RevcheckStatus::Untranslated )
            continue;

        if ( $path !== $file->path )
        {
            $path = $file->path;
            $header = $path == '' ? '/' : $path;
            print " <tr><th colspan='3'>$header</th></tr>";
        }

        $name = $file->name;
        $hash = $file->hashLast;
        $href = "https://github.com/php/doc-en/blob/{$hash}/$key";
        $size = round( $file->size / 1024 );

        print <<<HTML
 <tr class="bgorange">
  <td class="c"><a href="$href">$name</a></td>
  <td class="c">$hash</td>
  <td class="c">$size</td>
 </tr>
HTML;
    }
    print "</table>\n\n";
}

function print_html_footer()
{
    print_html_menu("");
    print <<<HTML
<p/>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
  var clipboard = new ClipboardJS('.btn');
  clipboard.on('success', function (e) {
     console.log(e);
  });
  clipboard.on('error', function (e) {
     console.log(e);
  });
</script>
</body>
</html>
HTML;
}

function print_debug_list( RevcheckData $data )
{
    foreach( $data->fileDetail as $key => $file )
        print "f:$key m:{$file->maintainer} s:{$file->status->value}\n";
    die();
}