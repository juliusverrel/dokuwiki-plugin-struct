<?php
/**
 * DokuWiki Plugin struct (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Iain Hallam <iain@nineworlds.net>
 */

use dokuwiki\plugin\struct\meta\AggregationValue;
use dokuwiki\plugin\struct\meta\ConfigParser;
use dokuwiki\plugin\struct\meta\SearchConfig;
use dokuwiki\plugin\struct\meta\StructException;

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_struct_value extends DokuWiki_Syntax_Plugin {

    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'substition';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        /* 155 to place above Doku_Parser_Mode_hr, which would otherwise
         * take precedence
         * See https://www.dokuwiki.org/devel:parser:getsort_list
         */
        return 155;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('----+ *struct value *-+\n.*?\n----+', $mode, 'plugin_struct_value');
    }

    /**
     * Handle matches of the struct syntax
     *
     * @param string $match The match of the syntax
     * @param int $state The state of the handler
     * @param int $pos The position in the document
     * @param Doku_Handler $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        global $conf;

        dbg('Reached struct value handler');

        $lines = explode("\n", $match);
        array_shift($lines);
        array_pop($lines);

        try {
            $parser = new ConfigParser($lines);
            $config = $parser->getConfig();
            return $config;
        } catch(StructException $e) {
            msg($e->getMessage(), -1, $e->getLine(), $e->getFile());
            if($conf['allowdebug']) msg('<pre>' . hsc($e->getTraceAsString()) . '</pre>', -1);
            return null;
        }
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string $mode Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array $data The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        if(!$data) return false;
        global $INFO;
        global $conf;

        try {
            $search = new SearchConfig($data);

            // limit to first result
            $search->setLimit(1);
            $search->setOffset(0);

            /** @var AggregationValue $value */
            $value = new AggregationValue($INFO['id'], $mode, $renderer, $search);
            $value->render();

            if($mode == 'metadata') {
                /** @var Doku_Renderer_metadata $renderer */
                $renderer->meta['plugin']['struct']['hasaggregation'] = $search->getCacheFlag();
            }

        } catch(StructException $e) {
            msg($e->getMessage(), -1, $e->getLine(), $e->getFile());
            if($conf['allowdebug']) msg('<pre>' . hsc($e->getTraceAsString()) . '</pre>', -1);
        }

        return true;
    }
}