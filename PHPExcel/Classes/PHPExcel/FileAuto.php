<?php
//本文件已被调整，以便按扩展名来处理已定义类型的文件，而不是任何文件都处理（默认的IOFactory文件是什么文件都处理...）

/**    PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
    /**
     * @ignore
     */
    define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../');
    require(PHPEXCEL_ROOT . 'PHPExcel/Autoloader.php');
}

class PHPExcel_IOFactory
{
    /**
     * Search locations
     *
     * @var    array
     * @access    private
     * @static
     */
    private static $searchLocations = array(
        array( 'type' => 'IWriter', 'path' => 'PHPExcel/Writer/{0}.php', 'class' => 'PHPExcel_Writer_{0}' ),
        array( 'type' => 'IReader', 'path' => 'PHPExcel/Reader/{0}.php', 'class' => 'PHPExcel_Reader_{0}' )
    );

    /**
     * Autoresolve classes
     *
     * @var    array
     * @access    private
     * @static
     */
    private static $autoResolveClasses = array(
        'Excel2007',
        'Excel5',
        'Excel2003XML',
        'OOCalc',
        'SYLK',
        'Gnumeric',
        'HTML',
        'CSV',
    );

    /**
     *    Private constructor for PHPExcel_IOFactory
     */
    private function __construct()
    {
    }

    /**
     * Get search locations
     *
     * @static
     * @access    public
     * @return    array
     */
    public static function getSearchLocations()
    {
        return self::$searchLocations;
    }

    /**
     * Set search locations
     *
     * @static
     * @access    public
     * @param    array $value
     * @throws    PHPExcel_Reader_Exception
     */
    public static function setSearchLocations($value)
    {
        if (is_array($value)) {
            self::$searchLocations = $value;
        } else {
            throw new PHPExcel_Reader_Exception('Invalid parameter passed.');
        }
    }

    /**
     * Add search location
     *
     * @static
     * @access    public
     * @param    string $type        Example: IWriter
     * @param    string $location    Example: PHPExcel/Writer/{0}.php
     * @param    string $classname     Example: PHPExcel_Writer_{0}
     */
    public static function addSearchLocation($type = '', $location = '', $classname = '')
    {
        self::$searchLocations[] = array( 'type' => $type, 'path' => $location, 'class' => $classname );
    }

    /**
     * Create PHPExcel_Writer_IWriter
     *
     * @static
     * @access    public
     * @param    PHPExcel $phpExcel
     * @param    string  $writerType    Example: Excel2007
     * @return    PHPExcel_Writer_IWriter
     * @throws    PHPExcel_Reader_Exception
     */
    public static function createWriter(PHPExcel $phpExcel, $writerType = '')
    {
        // Search type
        $searchType = 'IWriter';

        // Include class
        foreach (self::$searchLocations as $searchLocation) {
            if ($searchLocation['type'] == $searchType) {
                $className = str_replace('{0}', $writerType, $searchLocation['class']);

                $instance = new $className($phpExcel);
                if ($instance !== null) {
                    return $instance;
                }
            }
        }

        // Nothing found...
        throw new PHPExcel_Reader_Exception("No $searchType found for type $writerType");
    }

    /**
     * Create PHPExcel_Reader_IReader
     *
     * @static
     * @access    public
     * @param    string $readerType    Example: Excel2007
     * @return    PHPExcel_Reader_IReader
     * @throws    PHPExcel_Reader_Exception
     */
    public static function createReader($readerType = '')
    {
        // Search type
        $searchType = 'IReader';

        // Include class
        foreach (self::$searchLocations as $searchLocation) {
            if ($searchLocation['type'] == $searchType) {
                $className = str_replace('{0}', $readerType, $searchLocation['class']);

                $instance = new $className();
                if ($instance !== null) {
                    return $instance;
                }
            }
        }

        // Nothing found...
        throw new PHPExcel_Reader_Exception("No $searchType found for type $readerType");
    }

    /**
     * Loads PHPExcel from file using automatic PHPExcel_Reader_IReader resolution
     *
     * @static
     * @access public
     * @param     string         $pFilename        The name of the spreadsheet file
     * @return    PHPExcel
     * @throws    PHPExcel_Reader_Exception
     */
    public static function load($pFilename)
    {
        $reader = self::createReaderForFile($pFilename);
        if ($reader)
        	return $reader->load($pFilename);
        else 
        	return false;
    }

    /**
     * Identify file type using automatic PHPExcel_Reader_IReader resolution
     *
     * @static
     * @access public
     * @param     string         $pFilename        The name of the spreadsheet file to identify
     * @return    string
     * @throws    PHPExcel_Reader_Exception
     */
    public static function identify($pFilename)
    {
        $reader = self::createReaderForFile($pFilename);
        $className = get_class($reader);
        $classType = explode('_', $className);
        unset($reader);
        return array_pop($classType);
    }

    /**
     * Create PHPExcel_Reader_IReader for file using automatic PHPExcel_Reader_IReader resolution
     *
     * @static
     * @access    public
     * @param     string         $pFilename        The name of the spreadsheet file
     * @return    PHPExcel_Reader_IReader
     * @throws    PHPExcel_Reader_Exception
     */
    public static function createReaderForFile($pFilename)
    {
        // First, lucky guess by inspecting file extension
        $pathinfo = pathinfo($pFilename);

        $extensionType = null;
        $more = 0;
        if (isset($pathinfo['extension'])) {
            switch (strtolower($pathinfo['extension'])) {
                case 'xlsx':            //    Excel (OfficeOpenXML) Spreadsheet
                case 'xlsm':            //    Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded)
                case 'xltx':            //    Excel (OfficeOpenXML) Template
                case 'xltm':            //    Excel (OfficeOpenXML) Macro Template (macros will be discarded)
                    $extensionType = 'Excel2007';
                    break;
                case 'xls':                //    Excel (BIFF) Spreadsheet
                case 'xlt':                //    Excel (BIFF) Template
                    $extensionType = 'Excel5';
                    break;
                case 'ods':                //    Open/Libre Offic Calc
                case 'ots':                //    Open/Libre Offic Calc Template
                    $extensionType = 'OOCalc';
                    break;
                case 'slk':
                    $extensionType = 'SYLK';
                    break;
                case 'xml':                //    Excel 2003 SpreadSheetML
                    $extensionType = 'Excel2003XML';
                    break;
                case 'gnumeric':
                    $extensionType = 'Gnumeric';
                    break;
                case 'htm':
                case 'html':
                    $extensionType = 'HTML';
                    break;
                case 'csv':
                    $more = 1;
                    break;
                default:
                    break;
            }

            if ($extensionType !== null) {
                $reader = self::createReader($extensionType);
                // Let's see if we are lucky
                if (isset($reader) && $reader->canRead($pFilename)) {
                    return $reader;
                }
            }
        }

        if ($more){
	        foreach (self::$autoResolveClasses as $autoResolveClass) {
	            //    Ignore our original guess, we know that won't work
	            if ($autoResolveClass !== $extensionType) {
	                $reader = self::createReader($autoResolveClass);
	                if ($reader->canRead($pFilename)) {
	                    return $reader;
	                }
	            }
	        }
        }else
        	return false;
        throw new PHPExcel_Reader_Exception('Unable to identify a reader for this file');
    }
}
