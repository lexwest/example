<?php

abstract class myVisitor{
    
    abstract function visitEnter($block);
    abstract function visitExit($block);    
    
    abstract function setResult($value);
    
    protected $result = NULL;
    public $stop_flag = false;
    
    public function getResult(){
       
        return $this->result; 
        
    }
    
}


class gridBuilder extends myVisitor{
    
    public function visitEnter($block){
        
        if($block->parent != '0'){
            $result = $block->writeBuilderHTMLOfBlock();
            $this->setResult($result);
        }
        
    }
    
    public function visitExit($block){
                
        if($block->parent != '0'){
            $result = $block->closeHTMLOfBlock();

            $this->setResult($result);
        }
    }
    
    public function setResult($value) {
        
        $this->result .= $value;
        
    }
    
    
}

class gridViewer extends myVisitor{
    
    public function visitEnter($block){
        
        $result = $block->writeHTMLOfBlock();
        $this->setResult($result);

    }
    
    public function visitExit($block){
        
        $result = $block->closeHTMLOfBlock();
        
        
        $this->setResult($result);
    }
    
    public function setResult($value) {
        
        $this->result .= $value;
        
    }
    
}
        
class gridBlockCounter extends myVisitor{
    
    protected $result = 0;
    
    public function visitEnter($block){
        
        $this->result++;

    }
    
    public function visitExit($block){
        
    }

    public function setResult($value) {
        
        $this->result .= $value;
        
    }
 
}

class gridCSSRecorder extends myVisitor{
    
    public function visitEnter($block){
        
        $result = $block->writeCSSOfBlock();
        $this->setResult($result);

    }
    
    public function visitExit($block){

    }
    
    public function setResult($value) {
        
        $this->result .= $value;
        
    }
    
}

class gridBuilderListBar extends myVisitor{
    
    public function visitEnter($block){

        if($block->parent != '0'){
            $result = $block->writeListBarBlock();
            $this->setResult($result);
        }

    }
    
    public function visitExit($block){

    }
   
    public function setResult($value) {
        
        $this->result .= $value;
        
    }

}

class gridLastIdFinder extends myVisitor{
    
    public function visitEnter($block){
        
        preg_match('/\d+/', $block->id_block_css, $val);
        if(!empty($val[0]) && $this->result < $val[0]){
            $this->setResult($val[0]);
        }

    }
    
    public function visitExit($block){

    }
   
    public function setResult($value) {
        
        $this->result = $value;
        
    }

}


class gridUpdateBlock extends myVisitor{
    
    public $id_edited = NULL;
    public $attributes = array();

    public function visitEnter($block){
        
        if($block->id_block_css == $this->id_edited){
            
            foreach ($this->attributes as $key=>$value){
                $block->{$key} = $value;
                
            }
            
            $this->stop_flag = true;
        }
        
    }
    
    public function visitExit($block){

    }
   
    public function setResult($value) {
        
    }

}
class gridAddBlock extends myVisitor{
    
    public $attributes = array();

    public function visitEnter($block){

        if($block->id_block_css == $this->attributes->parent){

            if(strpos($this->attributes->id_block_css, 'cms') !== NULL)
                    $block_cls = new cms_box();
                else
                    $block_cls = new block();
                
            foreach ($this->attributes as $key=>$value){
                $block_cls->{$key} = $value;
            }
            
            $block_cls->child = array();
            
            $block->child[] = $block_cls;
            
            $this->stop_flag = true;
            
        }
        
    }
    
    public function visitExit($block){

    }
   
    public function setResult($value) {
        
    }

}

class gridRemoveBlock extends myVisitor{
    
    public $id_removed = NULL;
    public $parent = null;

    public function visitEnter($block){
    
        if(!empty($block->child)){
            for($i = 0; $i < count($block->child); $i++){
                if($block->child[$i]->id_block_css == $this->id_removed){
                    
                    array_splice($block->child, $i, 1);
//                    unset($block->child[$i]);
                    
                    $this->stop_flag = true;

                }
            }
        }
        
    }
    
    public function visitExit($block){
    }
   
    public function setResult($value) {
        
    }

}

class gridGetBranch extends myVisitor{
    
    public $id_block_css;
    protected $result = array();


    public function visitEnter($block){
    
        if($block->id_block_css == $this->id_block_css){
            
            $this->setResult($block->child);
            $this->stop_flag = true;
        }
            
        
    }
    
    public function visitExit($block){
    }
   
    public function setResult($value) {
        
        $this->result = $value;
        
    }
  
}

class gridUpdateBranch extends myVisitor{
    
    public $id_parent = NULL;
    public $branch = array();

    public function visitEnter($block){
//        echo $block->id_block_css . '__' . $this->id_parent . "\n";
        if($block->id_block_css == $this->id_parent){
//            echo 'norm';
            $block->child = unserialize(serialize($this->branch));
            
            $this->stop_flag = true;
        }
        
    }
    
    public function visitExit($block){

    }
   
    public function setResult($value) {
        
    }

}

class gridGetBlock extends myVisitor{
    
    public $id_block_css;
    protected $result = array();


    public function visitEnter($block){
    
        if($block->id_block_css == $this->id_block_css){
            
            $this->setResult($block);
            $this->stop_flag = true;
        }
            
        
    }
    
    public function visitExit($block){
    }
   
    public function setResult($value) {
        
        $this->result = $value;
        
    }
    
    
    
    
}
?>
