<?php

require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/taxon_name_parser.php');

$pp = new Parser();	

//--------------------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}

//--------------------------------------------------------------------------------------------------
// find name in BioNames
function bionames($canonical)
{
	$url = 'http://bionames.org/api/name/' . rawurlencode($canonical);
		
	$json = get($url);

	$obj =  json_decode($json);
	
	//print_r($obj);
	
	$result = new stdclass;
	$result->canonical = $canonical;
	
	if (count($obj->clusters) == 1)
	{
		foreach ($obj->clusters as $cluster)
		{
			foreach ($cluster->names as $name)
			{
				$result->ion = str_replace('urn:lsid:organismnames.com:name:', '', $name->id);
				
				if (isset($name->publishedInCitation))
				{
					$result->publishedInCitation = $name->publishedInCitation;	
						
					$url = 'http://bionames.org/api/api_citeproc.php?id=' . $result->publishedInCitation;
					$html = get($url);
					$result->citeproc = $html;				
				}
			}
		}
	}
	
	return $result;
}


//--------------------------------------------------------------------------------------------------

$basedir = '0000368-150512124619364';
$filename = $basedir . '/' . 'occurrence.txt';

$row_count = 0;

$file = @fopen($filename, "r") or die("couldn't open $filename");

$keys = array();

$ion_count = 0;

echo '<table>';
echo "\n";

echo '<tr>';
echo '<th>Occurrence</th>';
echo '<th>Holotype</th>';
echo '<th>GBIF matched name</th>';
echo '<th>Verbatim name</th>';
echo '<th>ION</th>';
echo '<th>BioNames</th>';
echo '<th>Publicaton</th>';
echo '</tr>';
echo "\n";
		
$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$row = fgetcsv(
		$file_handle, 
		0, 
		"\t"
		);
			
	if ($row_count == 0)
	{
		$column_count = 0;
		
		foreach ($row as $key => $value)
		{
			$keys[$value] = $column_count++;
		}		
		//print_r($keys);
	}
	else
	{
		$rows = array();
		foreach ($row as $key => $value)
		{
			$rows[] = $value;
		}		
		//print_r($rows);
		
		$obj = new stdclass;
		
		$obj->id = $rows[$keys['gbifID']];
		$obj->occurrenceID = $rows[$keys['occurrenceID']];
		
		$obj->cat = trim($rows[$keys['institutionCode']] . ' ' . $rows[$keys['catalogNumber']]);
		
		$obj->scientificName = $rows[$keys['scientificName']];
		
		$url = 'http://api.gbif.org/v1/occurrence/' . $obj->id . '/verbatim';
		
		$json = get($url);
		if ($json != '')
		{
			$verbatim = json_decode($json);
			
			$obj->verbatimScientificName = $verbatim->{"http://rs.tdwg.org/dwc/terms/scientificName"};
			$obj->verbatimScientificNameAuthorship = $verbatim->{"http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"};
			//print_r($verbatim);
			
			
			// clean name (sigh);
			$canonical = $obj->verbatimScientificName;
			$r = $pp->parse($obj->verbatimScientificName);
	
			if (isset($r->scientificName))
			{
				if ($r->scientificName->parsed)
				{
					$canonical = $r->scientificName->canonical;
				}
			}
			
			// look up in BioNames
			$result = bionames($canonical);
			
			//print_r($result);
			
			if (isset($result->ion))
			{
				$obj->ion = $result->ion;
				$ion_count++;
			}
			if (isset($result->publishedInCitation))
			{
				$obj->publishedInCitation = $result->publishedInCitation;
			}
			if (isset($result->citeproc))
			{
				$obj->citeproc = $result->citeproc;
			}
							
			
			
			
		}
		
		
		
		//print_r($obj);
		
		echo '<tr>';
		
		if (isset($obj->id)) { echo '<td>' . '<a href="http://gbif.org/occurrence/' . $obj->id . '" target="_new">' . $obj->id . '</a>' . '</td>'; } else { echo '<td></td>'; }
		if (isset($obj->cat)) { echo '<td>' . $obj->cat . '</td>'; } else { echo '<td></td>'; }
		if (isset($obj->scientificName)) { echo '<td>' . $obj->scientificName . '</td>'; } else { echo '<td></td>'; }
		if (isset($obj->verbatimScientificName)) { echo '<td>' . $obj->verbatimScientificName . '</td>'; } else { echo '<td></td>'; }
		if (isset($obj->ion)) { echo '<td>' . '<a href="http://www.organismnames.com/details.htm?lsid=' . $obj->ion . '" target="_new">' . $obj->ion . '</a>' . '</td>'; } else { echo '<td></td>'; }
		if (isset($obj->publishedInCitation)) { echo '<td>' . '<a href="http://bionames.org/references/' . $obj->publishedInCitation . '" target="_new">' . $obj->publishedInCitation . '</a>' . '</td>'; } else { echo '<td></td>'; }
		if (isset($obj->citeproc)) { echo '<td>' . $obj->citeproc . '</td>'; } else { echo '<td></td>'; }
		
		echo '</tr>';
		echo "\n";
		
		
	}
	
	
	if ($row_count == 10)
	{
		echo '</table>';
		echo '<p>' . $row_count . ' GBIF records, ' . $ion_count . ' (' . (100 * $ion_count/$row_count) . '%) in ION' . '</p>';

		exit();
	}
	
	$row_count++;		
}

echo "</table>\n";

echo '<p>' . $row_count . ' GBIF records, ' . $ion_count . ' (' . (100 * $ion_count/$row_count) . '%) in ION' . '</p>';

?>

