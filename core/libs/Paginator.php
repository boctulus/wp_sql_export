<?php declare(strict_types=1);

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\DB;
use boctulus\SW\core\libs\Strings;

class Paginator
{
    protected $orders = [];
    protected $offset = 0;
    protected $limit = null;
    protected $attributes = [];
    protected $query = '';
    protected $binding = [];
    protected $order;

    const TOP    = 'TOP';
    const BOTTOM = 'BOTTOM';

    /** 
     * @param array $attributes of the entity to be paginated
     * @param array $order 
     * @param int $offset
     * @param int $limit
    */
    function __construct($attributes = null, array $order = null, int $offset = 0, int $limit = null){
        $this->order = $order;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->attributes = $attributes;

        if ($order!=null && $limit!=null)
            $this->compile();
    }

    function compile():void
    {
        $query = '';
        if (!empty($this->orders)){
            $query .= ' ORDER BY ';
            
            foreach($this->orders as $field => $_order){
                $order = strtoupper($_order);

                if ((preg_match('/^[a-z0-9\-_\.]+$/i',$field) != 1)){
                    throw new \InvalidArgumentException("Field '$field' is not a valid field");
                }

                if ($order == 'ASC' || $order == 'DESC'){
                    $query .= "$field $order, ";
                }else
                    throw new \InvalidArgumentException("Order direction '$_order' is invalid. Order should be ASC or DESC!");   


                if (Strings::contains('.', $field)){
                    list($tb, $_field) = explode('.', $field);
                
                    if(!in_array($_field, $this->attributes)){
                        throw new \InvalidArgumentException("property '$field' not found!"); 
                    }
                }
                
            }
            $query = substr($query,0,strlen($query)-2);
        }

        $ol = [$this->limit !== null, !empty($this->offset)];
        //dd($ol, 'ol');

        // https://stackoverflow.com/questions/595123/is-there-an-ansi-sql-alternative-to-the-mysql-limit-keyword
        if ($ol[0] || $ol[1]){
            switch (DB::driver()){
                case 'mysql':
                case 'sqlite':
                    switch($ol){
                        case [true, true]:
                            $query .= " LIMIT ?, ?";
                            $this->binding[] = [1 , $this->offset, \PDO::PARAM_INT];
                            $this->binding[] = [2 , $this->limit,  \PDO::PARAM_INT];
                        break;
                        case [true, false]:
                            $query .= " LIMIT ?";
                            $this->binding[] = [1 , $this->limit, \PDO::PARAM_INT];
                        break;
                        case [false, true]:
                            // https://stackoverflow.com/questions/7018595/sql-offset-only
                            $query .= " LIMIT ?, 18446744073709551615";
                            $this->binding[] = [1 , $this->offset, \PDO::PARAM_INT];
                        break;
                    } 
                    break;    
                case 'pgsql': 
                    switch($ol){
                        case [true, true]:
                            $query .= " OFFSET ? LIMIT ?";
                            $this->binding[] = [1 , $this->offset, \PDO::PARAM_INT];
                            $this->binding[] = [2 , $this->limit,  \PDO::PARAM_INT];
                        break;
                        case [true, false]:
                            $query .= " LIMIT ?";
                            $this->binding[] = [1 , $this->limit, \PDO::PARAM_INT];
                        break;
                        case [false, true]:
                            $query .= " OFFSET ?";
                            $this->binding[] = [1 , $this->offset, \PDO::PARAM_INT];
                        break;
                    } 
                    break;            
            }
        }

        $this->query = $query;
    }

    function setAttr(Array $attributes) : Paginator {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Set the value of orders
     *
     * @return  self
     */ 
    function setOrders($orders): Paginator
    {
        $this->orders = $orders;
        return $this;
    }

    /**
     * Set the value of offset
     *
     * @return  self
     */ 
    function setOffset($offset): Paginator
    {
        $this->offset = $offset;
        return $this;
    }

    function getOffset(){
        return $this->offset;
    }

    /**
     * Set the value of limit
     *
     * @return  self
     */ 
    function setLimit($limit): Paginator
    {
        $this->limit = $limit;
        return $this;
    }

    function getLimit() {
        return $this->limit;
    }

     /**
     * Get the value of query
     */ 
    function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get the value of binding
     */ 
    function getBinding(): array
    {
        return $this->binding;
    }
}