<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgExtensionCredits['other'][] = array(
        'name' => 'AutoWIGO 2',
        'author' => '[http://rationalwiki.com/wiki/User:Nx Nx]',
        'url' => 'http://rationalwiki.com/',
        'description' => 'Automatically insert next poll number for WIGOs'
);

$wgAWIP = dirname( __FILE__ );
$wgExtensionMessagesFiles['AutoWIGO2'] = "$wgAWIP/AutoWIGO2.i18n.php";

$wgHooks['ArticleSave'][] = 'WIGOSave';

function WIGOSave(&$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags)
{
  /*global $wgRequest;
  $autowigoize = ( $wgRequest->getVal('autoWIGO',NULL) !== NULL );*/
  //if (!$autowigoize) return true;

  //(?:[^<]|<[^\/]|<\/[^n]|<\/n[^o]|<\/no[^w]|<\/now[^i]|<\/nowi[^k]|<\/nowik[^i]|<\/nowiki[^>])*
  $newnums = array();
  $matchi = preg_match_all('/(?<!<nowiki>)(<vote(cp|)\s*nextpoll=([^>]*)>)/i', $text,$matches,PREG_OFFSET_CAPTURE);
  if ($matchi > 0) $newtext = substr($text,0,$matches[1][0][1]);
  for ($i=0; $i<$matchi;++$i) {
    $curr = $matches[1][$i][0];
    $cp = $matches[2][$i][0];
    $pollid = $matches[3][$i][0];
    //fix pollid with numbers
    $pollid = preg_replace('/\d+$/', '', $pollid);
    
    //find the next id
    if ( array_key_exists($pollid,$newnums) ) {
      $num = $newnums[$pollid];
    } else {
      $num = 0;
    }
    $wigos = preg_split("/<\/vote(cp|)>/",$text);
    if (count($wigos) != 0) {
       for ($j = 0; $j<count($wigos); ++$j){
         $start = strpos($wigos[$j],"<vote");
         $wigos[$j] = substr($wigos[$j],$start);
         //$closetag = strpos($wigos[$j],">");
         //$pollstart = strpos($wigos[$j],"poll={$pollid}");
         $matchi2 = preg_match("/(?<!next)poll={$pollid}([^\s]*)/",$wigos[$j],$matches2);
         if ($matchi2 == 1) {
         //if ($pollstart !== False) {
           //$numstart = $pollstart+5+strlen($pollid);
           //$tempi = intval(substr($wigos[$j],$numstart,$closetag - $numstart));
           $tempi = intval($matches2[1]);
           //on error this will be 0, so we can skip error checking
           if ($tempi > $num) $num = $tempi;
         }
       }
    }
    ++$num;
    
    $newtag = "<vote{$cp} poll={$pollid}{$num}>";
    $nextlength = (($i == $matchi-1) ? (strlen($text) - ($matches[1][$i][1] + strlen($curr))) : ($matches[1][$i+1][1] - ($matches[1][$i][1] + strlen($curr))));
    $newtext .= $newtag //substr($text,$matches[1][$i][1],strlen($curr))
                . substr($text,$matches[1][$i][1]+strlen($curr),$nextlength);
    $newnums[$pollid] = $num;
  }
  if ($matchi > 0) $text = $newtext;
  return true;
}


