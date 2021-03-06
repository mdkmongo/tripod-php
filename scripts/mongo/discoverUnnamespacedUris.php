<?php
require_once dirname(__FILE__) . '/common.inc.php';

/**
 * Detects un-namespaced subjects or object uris in CBD collections of the target database. Optionally supply a base uri to match against that rather than all uris
 */
if ($argc!=4 && $argc!=3)
{
    echo "usage: php discoverUnnamespacedUris.php connStr database [baseUri]";
    die();
}

array_shift($argv);
/** @var \MongoDB\Client $client */
$client = new \MongoDB\Client(
    $argv[0],
    [],
    ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
);

/** @var \MongoDB\Database $db */
$db = $client->selectDatabase($argv[1]);

/**
 * @param string $uri
 * @param string|null $baseUri
 * @return bool
 */
function isUnNamespaced($uri,$baseUri=null)
{
    if ($baseUri==null)
    {
        return (strpos($uri,'http://')===0 || strpos($uri,'https://')===0);
    }
    else
    {
        return strpos($uri,$baseUri)===0;
    }
}

$results = array();
foreach ($db->listCollections() as $collectionInfo)
{

    /** @var \MongoDB\Collection $collection*/
    if (strpos($collectionInfo->getName(),'CBD_')===0) // only process CBD_collections
    {
        $collection = $db->selectCollection($collectionInfo->getName());
        echo "Checking out {$collectionInfo->getName()}\n";
        $count = 0;
        foreach ($collection->find() as $doc)
        {
            if (!isset($doc['_id']) || !isset($doc['_id']['r']))
            {
                echo "  Illegal doc: no _id or missing _id.r";
            }
            else
            {
                if (isUnNamespaced($doc['_id']['r'], (isset($argv[2]) ? $argv[2] : null) ))
                {
                    echo "  Un-namespaced subject: {$doc['_id']['r']}\n";
                    $count++;
                }
            }
            foreach ($doc as $property=>$value)
            {
                if (strpos($property,"_")===0) // ignore meta fields, _id, _version, _uts etc.
                {
                    continue;
                }
                else
                {
                    if (isset($value['l']))
                    {
                        // ignore, is a literal
                        continue;
                    }
                    else if (isset($value['u']))
                    {
                        if (isUnNamespaced($value['u'], (isset($argv[2]) ? $argv[2] : null)))
                        {
                            echo "  Un-namespaced object uri (single value): {$value['u']}\n";
                            $count++;
                        }
                    }
                    else
                    {
                        foreach ($value as $v)
                        {
                            if (isset($v['u']))
                            {
                                if (isUnNamespaced($v['u'], (isset($argv[2]) ? $argv[2] : null)))
                                {
                                    echo "  Un-namespaced object uri (multiple value): {$v['u']}\n";
                                    $count++;
                                }
                            }
                        }
                    }
                }
            }
        }
        $results[] = "{$collectionInfo->getName()} has $count un-namespaced uris";
        echo "Done with {$collectionInfo->getName()}\n";
    }
}
echo "\n".implode("\n",$results)."\n";

?>