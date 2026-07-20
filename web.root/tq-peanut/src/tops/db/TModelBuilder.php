<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 7/3/2017
 * Time: 12:51 PM
 */

namespace Tops\db;

/*****
 * Example build script: \tools\create-model.php
 */

use Tops\sys\TConfiguration;
use Tops\sys\TPath;
use \PDO;

class TModelBuilder
{
    /**
     * @var \PDO
     */
    private $dbh;
    private $databasekey = null;
    private  $namespace;
    private  $tempNamespace = 'Peanut\ORM';
    private  $prefixed;
    private  $overwrite;
    private $toolsRoot;
    private $plural;

    public static function Build($config, $toolsRoot) {
        (new TModelBuilder())->doBuild($config, $toolsRoot);
    }
    private function doBuild($config, $toolsRoot) {

        // $srcRoot=@$config['settings']['sourcePath'];

        // dset defaults from config
        $this->toolsRoot = $this->normalizePath($toolsRoot);
        $this->prefixed= $this->getArrayValueAsBool($config['settings'],'prefixed');
        $this->overwrite= $this->getArrayValueAsBool($config['settings'],'overwrite');
        $this->plural = $this->getArrayValue($config['settings'],'plural',1);
        $this->namespace = $this->getArrayValue($config['settings'],'namespace');


        // $include=array_keys($config['tables']);
        $tables = $config['tables'];
        $include = array_filter($tables, function($flag) {
            return $flag === '1'; // Change condition as needed
        });

        /*        $q  = $this->dbh->prepare("SHOW TABLES");
                $q->execute();
                $tables = $q->fetchAll(PDO::FETCH_COLUMN);

                foreach ($tables as $table) {
                    if (array_key_exists($table,$include)) {
                        $tableInfo = @$config[$table];
                        if (!is_array($tableInfo)) {
                            $tableInfo = [];
                        }
                        $this->buildSource($table,$tableInfo,$databaseKey);
                    }
                }*/

        $tables = array_keys($include);
        foreach ($tables as $table) {
            if (array_key_exists($table,$config)) {
                $tableInfo =  $config[$table] ;
            }
            else {
                $tableInfo = [];
            }

            $this->databasekey =  $config['settings']['databaseKey'] ?? null;
            $this->dbh = TDatabase::getConnection($this->databasekey);
            $this->buildSource($table,$tableInfo);
        }

        // var_dump($tables);

        print("\n\nBuild complete.\n");
    }

    private function getArrayValueAsBool($array,$key, $default=false) : bool {
        $value = $this->getArrayValue($array,$key);
        return ($value == '1') ? true: $default;
    }

    private function getArrayValue($array,$key,$default=null)
    {
        $result = $array[$key] ?? null;
        if (empty($result)) {
            return $default;
        }
        return $result;
    }

    private function buildSource($tableName,$params)
    {
        $namespace = $this->getArrayValue($params,'namespace',$this->namespace);

        $entityPath = $this->makeDirectory($this->toolsRoot,$namespace,'entity');
        $repositoryPath = $this->makeDirectory($this->toolsRoot,$namespace,'repository');

        $date = new \DateTime();
        if (empty($params)) {
            $params = array();
        }

        print "\nBuilding $tableName...";

        $databasekey = $this->getArrayValue($params,'databaseKey',null);
        $databaseId = empty($databasekey) ? 'null' : "'$databasekey'";

        $dbh = ($databasekey !== $this->databasekey) ?
            TDatabase::getConnection($databasekey) : $this->dbh;
        $q = $this->dbh->prepare("DESCRIBE $tableName");
        $q->execute();
        $fields = $q->fetchAll(PDO::FETCH_OBJ);

        $dtoTypes = [];

        $baseName = $this->getArrayValue($params,'basename');
        if (empty($baseName)) {
            $prefixed = $this->getArrayValueAsBool($params,'prefixed',$this->prefixed);
            $baseName = $this->baseNameFromTableName($tableName,$prefixed);
        }

        $repositoryName = $this->getArrayValue($params,'repository',$baseName.'Repository');

        $entityName = $this->getArrayValue($params,'entity');
        if (empty($entityName)) {
            $plural = $this->getArrayValue($params,'plural',$this->plural);
            $entityName = substr($baseName,0,0-(int)$plural);
        }

        // $buildEntity = strpos($entityName, '\\') === false;

        $lookupField = $this->getArrayValue($params,'lookupField');

        $entityProperties = array();
        $fieldDefs = array();
        $isTimestamped = false;
        $namedEntityFields = array(
            'id','name','description','code'
        );
        foreach ($fields as $field) {

            $fieldName = $field->Field;
            switch ($field->Field) {
                case 'createdon':
                case 'changedby':
                case 'changedon':
                case 'createdby' :
                    $isTimestamped = true;
                    $fieldDefs[] = "'$fieldName'=>PDO::PARAM_STR";
                    break;
                default:
                    switch ($field->Type) {
                        case 'datetime':
                            $dtoTypes[$fieldName] = "DateTime";
                            break;
                        case 'date' :
                            $dtoTypes[$fieldName] = "Date";
                            break;
                        case 'tinyint(1)' :
                            if ($fieldName != 'active') {
                                $dtoTypes[$fieldName] = "Flag";
                            }
                            break;
                        case 'time' :
                            // todo: supported later
                            break;
                    }
                    $entityProperties[$field->Field] = '    public $' . $field->Field . ";";
                    $type = explode('(', $field->Type)[0];

                    $type = (($type == 'int' || $type=='int unsigned') && $field->Null === 'NO') ? 'INT' : 'STR';
                    $fieldDefs[] = "'$fieldName'=>PDO::PARAM_$type";
                    break;
            }
        }


        $repositorySuperclass = '\Tops\db\TEntityRepository';
        $propertyCount = sizeof($entityProperties);
        $isNamedEntity = false;

        if ($isTimestamped) {
            if (array_key_exists('id',$entityProperties) &&
                array_key_exists('name',$entityProperties) &&
                array_key_exists('code',$entityProperties) &&
                array_key_exists('description',$entityProperties) ) {
                $superclass = 'NamedEntity';
                $isNamedEntity = true;
                $lookupField = null;
                unset($entityProperties['id']);
                unset($entityProperties['name']);
                unset($entityProperties['code']);
                unset($entityProperties['description']);
                unset($entityProperties['active']);
                $repositorySuperclass = '\Tops\db\TNamedEntitiesRepository';
                if (sizeof($entityProperties) == 0) {
                    // this is a generic lookup table, use NamedEntity
                    $propertyCount = 0;
                    $buildEntity = false;
                    $fullClassName = '\Tops\db\NamedEntity';
                    $lookupField = null;
                }
            }
            else {
                if (array_key_exists('id',$entityProperties)) {
                    $superclass = 'TEntity';
                    unset($entityProperties['id']);
                }
                else {
                    $superclass =  'TimeStampedEntity';
                }
            }
        }
        else {
            $superclass = 'TAbstractEntity';
        }

        $superclass =  isset($superclass) ?  ' extends \Tops\db\\'.$superclass : '';
        $dto ='$dto';
        $entity =
            "<?php \n" .
            "/** \n" .
            " * Created by /tools/create-model.php \n" .
            " * Time:  " . $date->format('Y-m-d H:i:s') . "\n" .
            " */ \n\n" .
            "namespace ". $namespace ."\\entity;" . "\n\n" .
            // "namespace Peanut\\ORM\\entity;" . "\n\n" .
            "class $entityName $superclass \n" .
            "{ \n" .
            join("\n", array_values($entityProperties))."\n\n";

        if (!empty($dtoTypes)) {
            $entity .= "    public function getDtoDataTypes()\n    {\n        ".'$'."types = parent::getDtoDataTypes();\n";
            foreach ($dtoTypes as $propertyName => $dtoType) {
                $entity .= '        $'.sprintf("types['%s'] = \Tops\sys\TDataTransfer::dataType%s;\n",$propertyName,$dtoType);
        }
            $entity .= '        return $types;'."\n    }\n";
        }

        $entity .= "}\n";

        $fullClassName = $namespace."\\entity\\" . $entityName;

        $code = array(
            "<?php ",
            "/** ",
            " * Created by /tools/create-model.php ",
            " * Time:  " . $date->format('Y-m-d H:i:s'),
            " */ \n" .
            "namespace $namespace;\n",
            // "namespace Peanut\\ORM\\repository;\n",
            '',
            'use \PDO;',
            'use PDOStatement;',
            'use Tops\db\TDatabase;',
            "use $repositorySuperclass;",
            "use $fullClassName;",
            '',
            "class $repositoryName extends $repositorySuperclass",
            "{",
            "    protected function getTableName() {",
            "        return '$tableName';",
            "    }",
            "",
            "    protected function getDatabaseId() {",
            "        return $databaseId;",
            "    }",
            "");


        if ($propertyCount > 0) {
            $code[] = "    protected function getClassName() {";
            $code[] ="         return '$fullClassName';";
            // $code[] ="        return null; // delete and uncomment above for deployment";
            $code[] ="    }";
            $code[] ="";
            $code[] ="    protected function getFieldDefinitionList()";
            $code[] ="    {";
            $code[] ="        return array(";

            $last = sizeof($fieldDefs);
            $count = 0;
            foreach ($fieldDefs as $def) {
                $count++;
                $code[] = "        $def".($count == $last? ');' : ',');
            }
            $code[] = '    }';

            if (!empty($lookupField)) {
                $code[] =  "    protected function getLookupField() {";
                $code[] =  "        return '$lookupField';";
                $code[] =  "    }";
            }
        }

        $code[] = '}';

        $repos = join("\n",$code);

        $this->writeFile($entityPath,$entityName,$entity);
        $this->writeFile($repositoryPath,$repositoryName,$repos);
        print("\n");
    }

    private  function writeFile($filePath,$classFile, $data)
    {
        print "\nWriting '$classFile'...";
        $fileName = sprintf("%s/%s.php",$filePath,$classFile);

        if ($this->overwrite || !file_exists($fileName)) {
            file_put_contents($fileName,$data);
        }
        else {
            print "\nFile '$classFile' exists. Skipping...";
        }
    }


    private  function makeDirectory($root,$dirname,$sub='')
    {
        $dirname = $root . '/output/'.$dirname;;
        if ($sub) {
            $dirname .= '/'.$sub;
        }
        $dirname = str_replace('\\','/',$dirname);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777,true);
        }
        return $dirname;
    }

    private  function normalizePath($path)
    {
        $path = trim($path);
        if (strpos($path,':') === 1) {
            $path = substr($path,2);
        }
        return str_replace('\\','/',$path);
    }


    /**
     * @param $tableName
     * @return bool|string
     */
    private function entityNameFromBaaeName($baseName)
    {
        $plural = substr($baseName, strlen($baseName) - 1);
        if ($plural == 's') {
            return  substr($baseName, 0, strlen($baseName) - 1);
        }
        return $baseName;
    }

    private function baseNameFromTableName($tableName,$prefixed) : String
    {
        $result = '';
        $parts = explode('_', $tableName);
        if (sizeof($parts) > 1 && $prefixed) {
            array_shift($parts);
        }
        foreach ($parts as $part) {
            $result .= strtoupper(substr($part, 0, 1)) . substr($part, 1);
        }

        return $result;
    }

}