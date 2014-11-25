<?php
require_once( dirname(__FILE__) . '/../database.php');
require_once( dirname(__FILE__) . '/block.php');
require_once( dirname(__FILE__) . '/cms_box.php');
require_once 'visitors.php';

class blockManager extends DB_Class {

	static $grid_size = 12;
	static $box_height_count = 128;
	static $box_width_count = 80;
	
	public $blocks = array();
	public $last_box_id;
	public $history_back;
	public $history_forward;
	public $tool_bar_box_display;
	public $temp_full_tree;
        
        function copyFromBM($bm){
            
        }
        
	function __construct($bm=NULL) {
                if($bm){
                    $this->copyFromBM($bm);
                    return;
                }

		$this->tableName = '';

		if (!isset($_SESSION)) {
			session_start();
		}
		if (!isset($_SESSION['last_template']))
			$_SESSION['last_template'] = NULL;

		$this->blocks = (!empty($_SESSION['blocks'])) ? $_SESSION['blocks'] : array('last_action' => 'Clear list', 'storage' => array());

		$this->history_back = (!empty($_SESSION['history_back'])) ? $_SESSION['history_back'] : array();
		$this->history_forward = (!empty($_SESSION['history_forward'])) ? $_SESSION['history_forward'] : array();
		$this->tool_bar_box_display = (int)((blockManager::$grid_size * blockManager::$box_width_count + 5 * blockManager::$grid_size) / (blockManager::$grid_size * 6));
		
                $this->temp_full_tree = (!empty($_SESSION['temp_full_tree'])) ? $_SESSION['temp_full_tree'] : array('last_action' => 'Clear list', 'storage' => array());
                
                if (!empty($this->blocks['storage'])){
                    
                    $this->last_box_id = $this->getLastBoxId($this->blocks['storage']);
                    
		}
		else{
			$this->last_box_id = 0;
		}

		$this->connect();
	}

	function __destruct() {
		parent::__destruct();

		$_SESSION['blocks'] = $this->blocks;
		$_SESSION['history_forward'] = $this->history_forward;
		$_SESSION['history_back'] = $this->history_back;
                $_SESSION['temp_full_tree'] = $this->temp_full_tree;
	}
        
        public function changeSize($height = NULL, $width = NULL){
            
            if($height != NULL)
                self::$box_height_count = $height;
            
            if($width != NULL)
                self::$box_width_count = $width;
            
        }


        function createBlock($attributes){

            if(empty($attributes->parent)){
                $attributes->parent = 'box_0';
            }
            $visitor = new gridAddBlock();

            $visitor->attributes = $attributes;

            $this->boxTreeWalker($this->blocks['storage'], $visitor);

            $this->blocks['last_action'] = $attributes->id_block_css . ' block is added';
            return true;

	}

	function updateBlock($id, $attributes){

            $visitor = new gridUpdateBlock();

            $visitor->id_edited = $id;

            $visitor->attributes = $attributes;

            $this->boxTreeWalker($this->blocks['storage'], $visitor);
            $this->blocks['last_action'] = $attributes->id_block_css . ' block is updated';

	}
	
	function updateBranch($id){

            $visitor = new gridUpdateBranch();

            $visitor->id_parent = $id;


            $visitor->branch = $this->blocks['storage'];

            $this->boxTreeWalker($this->temp_full_tree['storage'], $visitor);
            


	}

	function removeBlock($id){

            $visitor = new gridRemoveBlock();

            $visitor->id_removed = $id;
            
            $visitor->parent = &$this->blocks['storage'];
            
            $removed = FALSE;

            for($i = 0; $i < count($this->blocks['storage']); $i++){
                
                if($this->blocks['storage'][$i]->id_block_css == $id){
//                   unset($this->blocks['storage'][$i]);
                   array_splice($this->blocks['storage'], $i, 1);
                   $removed = TRUE;
                }
            }

            if(!$removed)
                $this->boxTreeWalker($this->blocks['storage'], $visitor);
		$this->blocks['last_action'] = $id . ' block is removed';
	}

	function clearGrid(){

		$this->blocks['last_action'] = 'Grid is cleared ';
		$this->blocks['storage'] = array();

	}

	public function loadBlocks($id_page = NULL, $parent = 0){


		$this->blocks = array('last_action' => 'Downloading from DB', 'storage' => array());

		$this->history_back = array();
		$this->history_forward = array();



		$this->blocks['storage'] = $this->getBoxesTree($id_page, $parent);

		if (!empty($this->blocks['storage'])){
                    
                    $this->last_box_id = $this->getLastBoxId($this->blocks['storage']);
                    
		}
		else{
			$this->last_box_id = 0;
		}


	}
        
        public function loadBranch($parent){


		$this->blocks = array('last_action' => 'Opening branch from a tree', 'storage' => array());

		$this->history_back = array();
		$this->history_forward = array();


                $visitor = new gridGetBranch();
                
                $visitor->id_block_css = $parent;
		
                $this->blocks['storage'] = $this->boxTreeWalker($this->temp_full_tree['storage'], $visitor);

		if (!empty($this->temp_full_tree['storage'])){
                    
                    $this->last_box_id = $this->getLastBoxId($this->temp_full_tree['storage']);
                    
		}
		else{
			$this->last_box_id = 0;
		}


	}
        
        public function loadTemplate(){

            $this->blocks = array('last_action' => 'Load template', 'storage' => array());

            $this->history_back = array();
            $this->history_forward = array();
            $this->last_template = NULL;
            $this->last_box_id = 0;
            $this->blocks = $_SESSION['template_blocks'];
            $_SESSION['last_template'] = NULL;

	}


        public function getLastBoxId($blocks){

            return (int)$this->boxTreeWalker($blocks, 'gridLastIdFinder');
            
        }

	public function savePage($id_page, $new = false){


		$blocks = $this->getBlocksObjects($id_page, true);

		$old_blocks = array_values($this->getBlocks($id_page));


		for($i=0; $i<count($blocks); $i++){

			if(!empty($blocks[$i]->id) && !$new){

				for($j = 0; $j < count($old_blocks); $j++){
					if ($old_blocks[$j]['id'] == $blocks[$i]->id){
						unset($old_blocks[$j]);
						break;
					}
				}
				$blocks[$i]->_save();
			}
			else{
				$blocks[$i]->initParams();
			}


			$old_blocks = array_values($old_blocks);

		}


		if(!empty($old_blocks)){

			$where = ' WHERE id_grid_pages_block = ' . $old_blocks[0]['id'];

			for ($i = 1; $i < count($old_blocks); $i++){
				$where .= ' OR id_grid_pages_block = ' . $old_blocks[$i]['id'];
			}

			$query = '
				DELETE FROM grid_pages_blocks ' . $where;

			$this->executeQuery(sprintf($query), '');

		}


		$this->loadBlocks($id_page);

	}
        
        
        public function getBoxById($id_box){
            
            $query = '
                 Select *, id_grid_pages_block as id
                from grid_pages_blocks
                LEFT JOIN grid_pages on grid_pages.id_grid_page = grid_pages_blocks.page_id ' .
                sprintf(' WHERE id_grid_pages_block = %d ', $this->GetSQLValueString($id_box, 'int'));
            
            $block_pointer = $this->executeQuery(sprintf($query), 'Select block by id');
            
            return mysql_fetch_object($block_pointer);
            
            
        }
        
        public function getBoxes($id_page = NULL, $id_parent = 0){
            
            $query = '
                Select *, id_grid_pages_block as id
                from grid_pages_blocks WHERE 1 = 1 ';
            
            if($id_page)
                $query .= ' AND page_id = ' . $id_page;
                        
            $query .= ' AND parent = "' . $id_parent . '"';
                
            $block_pointer = $this->executeQuery(sprintf($query), 'Select block by id');
            $block = array();
            
            while ($row = mysql_fetch_assoc($block_pointer)){
                if(strpos($row['id_block_css'], 'cms') !== NULL)
                    $block_cls = new cms_box();
                else
                    $block_cls = new block();
                
                $block_cls->initRow($row);
                $block[] = $block_cls;
            }
            
            return $block;
            
        }
        
        public function getBoxesTree($id_page = NULL, $id_parent = NULL){
            

            $childrens = $this->getBoxes($id_page, $id_parent);
  
            if(empty($childrens)){
                return array();
            }
            else {
                for($i = 0; $i < count($childrens); $i++){
                    $childrens[$i]->child = $this->getBoxesTree($id_page, $childrens[$i]->id_block_css);
                }
                return $childrens;
            }
            
        }
        
        public function boxTreeWalker($tree, $visitor){

            if(!is_object($visitor))
                $visitor = new $visitor();
            
            foreach ($tree as &$one){
                $visitor->visitEnter($one);
                
                if($visitor->stop_flag)
                    return $visitor->getResult();
                
                if(!empty($one->child)){                    
                    $this->boxTreeWalker($one->child, $visitor);                    
                }
                $visitor->visitExit($one);                
                
            }

            
            return $visitor->getResult();
        }

}