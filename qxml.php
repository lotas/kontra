<?php
echo '<?xml version="1.0" encoding="utf8"?>';

include("initdb.php");
include("funcs.php");

$url = "http://mf.yaraslav.com/kontra.php?action=read&amp;qid=";
$entries = getLatestQuestions();

?> 
<rdf:RDF 
 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 xmlns="http://purl.org/rss/1.0/">
 
 <channel rdf:about="http://mf.yaraslav.com/qxml.php">
   <title>MF Zone - Latest Questions</title>
   <link>http://mf.yaraslav.com/</link>
   <description>
     MF-Community 
   </description>
   <image rdf:resource="http://mf.yaraslav.com/images/design_05.jpg" />
   <items>
     <rdf:Seq>
	<?php foreach($entries as $row) { ?>
       <rdf:li rdf:resource="<?=$url.$row['qid']?>" />
	<?php } ?>
     </rdf:Seq>
   </items>   
 </channel>

<?php foreach($entries as $row) { ?> 
 <item rdf:about="<?=$url.$row['qid']?>">
   <title><?=substr($row['question'], 0, 40) . '...'?></title>
   <link><?=$url.$row['qid']?></link>
   <description>
		<b><?=$row['nick']?></b> asked on <?=$row['data']?><br/>
		<br/>
		<?=$row['question']?>
   </description>
 </item>
<?php } ?>
 
</rdf:RDF>