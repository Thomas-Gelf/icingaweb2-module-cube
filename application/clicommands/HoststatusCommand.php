<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Cube\Clicommands;

use ArrayIterator;
use Icinga\Module\Cube\Ido\IdoHostStatusCube;
use Icinga\Module\Cube\Ido\CustomVarDimension;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Cli\CliUtils;
use Icinga\Cli\Command;
use Icinga\Cli\AnsiScreen;
use Icinga\Module\Cube\Controllers\IndexController;
use Icinga\Data\Db\DbQuery;
use Icinga\File\Csv;
use Icinga\Exception\MissingParameterException;

/**
 * cube for host status
 */
class HostStatusCommand extends Command
{
    //  protected $defaultActionName = 'dimensions';
    
    public function init()
    {
        $this->app->setupZendAutoloader();
        //        $this->dumpSql = $this->params->shift('showsql');
        $this->setFormat($this->params->shift('format'));
        $this->cube = new IdoHostStatusCube();
        $this->setDimensionParams($this->params->shift('dimensions'));
        $this->cube->chooseFacts(array_keys($this->cube->getAvailableFactColumns()));
        $this->screen  = new AnsiScreen();
        $this->utils   = new CliUtils($this->screen);
        $this->maxCols = $this->screen->getColumns();
    }
    
    public function setDimensionParams($aDimensions = "")
    {
        $this->dimensions = "" . $aDimensions;
        // $this->cube->addDimensionByName("iTyp");
        $vars             = preg_split('/,/', $this->dimensions, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($vars as $var) {
            $this->cube->addDimensionByName($var);
        }
        return $this;
    }
    
    
    /**
     * list facts for the cube
     *
     * USAGE
     *
     *   icingacli cube hoststatus facts
     */
    public function factsAction()
    {
        $screen = $this->screen;
        print $screen->underline($this->translate('Cube') . " - " . $this->translate('facts') . "\n");
        foreach ($this->cube->listFacts() as $aFact) {
            print "-> " . $aFact . "\n";
        }
        ;
    }
    
    /**
     * list the columns of the requestet cube
     *
     * USAGE
     *
     *   icingacli cube hoststatus columns --dimensions=<DIMENSIONS>
     *
     * DIMENSIONS
     *
     *   A comma separated list of dimensions
     *
     * EXAMPLES
     *
     *   icingacli cube hoststatus columns --dimensions=OS
     */
    public function columnsAction()
    {
        if ($this->formatIsJson()) {
            echo json_encode($this->cube->listColumns());
            return;
        }
        
        if ($this->formatIsCsv() && 0 == 1) {
            showArray($this->cube->listColumns());
            //      Csv::fromQuery(new ArrayIterator($this->cube->listColumns()))->dump();
            return;
        }
        
        $screen = $this->screen;
        print $screen->underline($this->translate('Cube') . " - " . $this->translate('columns') . "\n");
        foreach ($this->cube->listColumns() as $aColumn) {
            print "-> " . $aColumn . "\n";
        }
    }
    
    /**
     * show addtitional dimensions (doesn't work)
     *
     */
    public function additionalAction()
    {
        $screen = $this->screen;
        print $screen->underline($this->translate('Cube') . " - " . $this->translate('additional dimensions') . "\n");
        
        foreach ($this->cube->listAdditionalDimensions() as $aAdditional) {
            print "-> " . $aAdditional . "\n";
        }
    }
    
    
    /**
     * return a CSV representation of an array
     *
     * @return string
     */
    protected function getCsvFromArray($aArray, $aSep = ',')
    {
        return implode($aSep, $aArray) . "\r\n";
        
        $first = true;
        $csv   = '';
        foreach ($aArray as $row) {
            if ($first) {
                $csv .= implode(',', array_keys((array) $row)) . "\r\n";
                $first = false;
            }
            $out = array();
        }
        
        return $csv;
    }
    
    /**
     * helper for show an array
     */
    protected function showArray($aArray)
    {
        $aArrayIterator = new ArrayIterator($aArray);
        if ($this->formatIsJson()) {
            echo json_encode($aArrayIterator);
            return;
        }
        
        if ($this->formatIsCsv()) {
            echo $this->getCsvFromArray($aArray);
            return;
        }
        
        print_r($aArray);
    }
    
    
    /**
     * fetch data from request cube, option --dimensions=<dimensionslist> required
     *
     * USAGE
     *
     *   icingacli cube hoststatus list --dimensions=[dimensions] [options]
     *
     * OPTIONS
     *
     *   --format=<csv|json|sql>
     *
     * EXCEPTIONS
     *
     *   MissingParameter is thrown if dimensions is not given
     *
     * EXAMPLE
     *
     *   icingacli cube hoststatus list --dimensions=OS
     */
    public function listAction()
    {
        if (!$this->dimensions) {
            throw new MissingParameterException('dimensions option is missing');
        }
        
        if ($this->formatIsSql()) {
            $this->fullquery();
            return;
        }
        
        if ($this->formatIsJson()) {
            echo json_encode($this->cube->fetchAll());
            return;
        }
        
        if ($this->formatIsCsv()) {
            Csv::fromQuery(new ArrayIterator($this->cube->fetchAll()))->dump();
            return;
        }
        
        $screen = $this->screen;
        print $screen->underline("Cube: " . $this->cube->getDimensionsLabel() . "\n");
        print_r($this->cube->fetchAll());
    }
    
    /**
     * list slices (doesn't work)
     * TBD.
     */
    public function listSlicesAction()
    {
        print_r($this->cube->listSlices());
        return $this;
    }
    
    /**
     * show the sql query for the cube.
     * This is only usefull for developer.
     */
    public function fullquery()
    {
        print $this->cube->fullquery() . "\r\n";
        return $this;
    }
    
    /**
     *  show the sql query with no null values in the cube.
     *  This is only usefull for developer.
     */
    public function fullqueryNoNullAction()
    {
        print $this->getFullQuery(true) . "\r\n";
    }
    
    /**
     * show examples
     *
     * USAGE
     *
     *   icingacli cube hoststatus examples
     */
    public function examplesAction()
    {
        $screen = $this->screen;
        print $screen->underline("Examples");
        print "examplesAction\n";
        print "list all avaible dimensions.\n";
        print "   icingacli cube hoststatus dimensions\n";
    }
    
    /**
     * show information
     *
     *  Show infos for the dimenions, dimensions label, format and dbname (not working, TODO)
     *
     * USAGE
     *
     *   icingacli cube hoststatus info [--dimensions=DIMENSIONS]
     *
     * DIMENSIONS
     *
     *      dime
     *
     * EXAMPLE
     *
     *   icingacli cube hoststatus info --dimensions=OS
     */
    public function infoAction()
    {
        print "info\n";
        print "====\n";
        print "dimensions: " . $this->dimensions . "\n";
        print "dimensions label: " . $this->cube->getDimensionsLabel() . "\n";
        //       print "facts: ".$this->cube->getFacts()."\n";
        print "format: " . $this->format . "\n";
        print "dbname: " . $this->cube->getDbName() . "\n";
        print "\n";
    }
    
    /**
     * list all available dimensions
     *
     * USAGE
     *
     *   icingcli cube hoststatus dimensions
     */
    public function dimensionsAction()
    {
        $screen = $this->screen;
        print $screen->underline($this->translate('Cube') . " - " . $this->translate('Dimensions') . "\n");
        foreach ($this->cube->listAvailableDimensions() as $aDim) {
            print "-> " . $aDim . "\n";
        }
    }
    
    /**
     * Set the wanted output format
     */
    public function setFormat($aFormat = 'screen')
    {
        $this->format = $aFormat;
        if ($this->format == '') {
            $this->format = 'screen';
        }
        return $this;
    }
    
    /**
     * Test if output format is CSV
     * @return boolean - true if output should be csv, otherwise false
     */
    public function formatIsCsv()
    {
        return ($this->format === 'csv');
    }
    
    /**
     * Test if output format should be json
     * @return boolean - true if output should be in json, otherwise false
     */
    public function formatIsJson()
    {
        return ($this->format === 'json');
    }
    
    public function formatIsSql()
    {
        return ($this->format === 'sql');
    }
    
    /**
     * Return the underlying cube sql query (developer)
     *
     * @return String
     */
    public function getFullQuery($withWrap = false)
    {
        if ($withWrap) {
            $aReturn = "select * from ( " . $this->cube->fullquery() . ") as CUBE where 0=0";
            $vars    = preg_split('/,/', $this->dimensions, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($vars as $var) {
                $aReturn .= " AND " . $var . " is not null";
            }
            
            return $aReturn;
        }
        
        return $this->cube->fullquery();
    }
}
