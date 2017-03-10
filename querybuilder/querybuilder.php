<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2017/3/8
 * Time: 18:32
 * 用来拼接查询字符串
 */
class queryBuilder {

    /**csw
     * 保存的sql语句
     */
    private $sql = '';

    /**csw
     * sql语句的类型 select,insert,update,delete,replace
     */
    private $sql_type;

    /**csw
     * 保存更新操作相关信息
     */
    private $qb_update;

    /**csw
     * 删除操作相关信息
     */
    private $qb_delete;

    /**csw
     * 替换操作相关信息
     */
    private $qb_replace;

    /**保存select相关信息
     * @var array
     */
    private $qb_select = array();

    /**保存数据库相关信息
     * @var array
     */
    private $qb_from = array();

    /**csw
     * 保存所有where信息
     */
    private $qb_wh = array();

    /**排序参照字段
     * @var array
     */
    private $qb_order_by = array();

    /**limit offset
     */
    private $limit = array();

    //是否开启字段保护
    private $field_protect = true;

    //字段保护用的标识符
    private $field_identifier = '`';

    //记录错误信息
    private $err_msg = array();
    /**
     * queryBuilder constructor.
     * @param $field_protect
     */
    public function __construct($field_protect='')
    {
        if(is_bool($field_protect))
        {
            $this->field_protect = $field_protect;
        }
    }

    /**csw
     * 如果开启
     * @param $v
     * @param $is_field
     * @return string
     */
    private function _protect_identifiers($v,$is_field)
    {
        if($is_field)
        {
            if($this->field_protect)
                return $this->field_identifier.$v.$this->field_identifier;

            return $v;
        }
        return "'{$v}'";
    }

    /**csw
     * 获取错误信息
     */
    public function getError()
    {
        return $this->err_msg;
    }

    /**csw
     * 封装错误信息
     * @param $errNo
     * @param string $msg
     * @param string $method
     */
    private function addError($errNo,$msg='',$method='')
    {
        $msg = empty($method) ? $msg : "方法#{$method}#提示信息#{$msg}#";
        $this->err_msg[] = array(
            'errNo' =>  $errNo,
            'errMsg'=>  $msg
        );
    }

    /**保存select信息
     * @param string $select
     * @return $this
     */
    public function select($select='*')
    {
        if(is_string($select) && $select !='*')
        {
            $select = explode(',',$select);

            foreach ($select as $val)
            {
                $val = trim($val);

                if ($val !== '')
                {
                    $this->qb_select[] = $this->_protect_identifiers($val,true);
                }
            }
        }
        else
        {
            $this->qb_select[] = '*';
        }
        $this->sql_type = 'select';
        return $this;
    }

    /**csw
     * select count(*) ...
     * @param $field
     * @return $this
     */
    public function select_count($field='*')
    {
        ($field=='*') or $field = $this->_protect_identifiers($field,true);
        $this->qb_select[] = "COUNT({$field})";
        $this->sql_type = 'select';
        return $this;
    }

    /**csw
     * Insert into ___(,,,) values (,,,)
     * @param $table    表名称
     * @param array $data 需要做插入操作的数据
     * @return string
     */
    public function insert($table,array $data)
    {
        $table = $this->_protect_identifiers($table,true);
        $fieldArr = array();
        $valArr = array();
        foreach ($data as $key=>$val)
        {
            $fieldArr[] = $this->_protect_identifiers($key,true);
            $valArr[] = $this->_protect_identifiers($val,false);
        }
        $fieldStr = '('.implode(',',$fieldArr).')';
        $valStr = '('.implode(',',$valArr).')';
        $sql = "INSERT INTO {$table} {$fieldStr} VALUES {$valStr}";
        $this->sql = $sql;
        $this->sql_type = 'insert';
        return $this;
    }

    /**csw
     * 批量添加
     * @param $table
     * @param array $fields
     * @param array $data
     * @internal param array $field
     * @return string
     */
    public function batch_insert($table,array $fields,array $data)
    {
        $table = $this->_protect_identifiers($table,true);
        foreach ($fields as $key=>$val)
        {
            $fieldArr[] = $this->_protect_identifiers($val,true);
        }
        $fieldStr = '('.implode(',',$fieldArr).')';
        foreach ($data as $key=>$val)
        {
           foreach ($val as $ke=>$va)
           {
               $val[$ke] = $this->_protect_identifiers($va,false);
           }
           $valArr[] = '('.implode(',',$val).')';
        }
        $valStr = implode(',',$valArr);
        $sql ="INSERT INTO {$table} {$fieldStr} VALUES {$valStr}";
        $this->sql = $sql;
        $this->sql_type = 'insert';
        return $this;
    }

    /**csw
     * 更新
     * @param $table
     * @param array $data
     * @return $this
     */
    public function update($table,array $data)
    {
        $table = $this->_protect_identifiers($table,true);
        $strArr = array();
        foreach ($data as $key=>$val)
        {
            $key = $this->_protect_identifiers($key,true);
            $val = $this->_protect_identifiers($val,false);
            $strArr[] = $key.'='.$val;
        }
        $str = implode(',',$strArr);
        $str = "UPDATE {$table} SET {$str}";
        $this->qb_update = $str;
        $this->sql_type = 'update';
        return $this;
    }

    /**csw
     * replace
     */
    public function replace($table,$data)
    {
        $table = $this->_protect_identifiers($table,true);
        $fieldArr = array();
        $valArr = array();
        foreach ($data as $key=>$val)
        {
            $fieldArr[] = $this->_protect_identifiers($key,true);
            $valArr[] = $this->_protect_identifiers($val,false);
        }
        $fieldStr = '('.implode(',',$fieldArr).')';
        $valStr = '('.implode(',',$valArr).')';
        $sql = "REPLACE INTO {$table} {$fieldStr} VALUES {$valStr}";
        $this->sql = $sql;
        $this->sql_type = 'replace';
        return $this;
    }

    /**csw
     * 删除
     * @param $table
     * @return $this
     */
    public function delete($table)
    {
        $table = $this->_protect_identifiers($table,true);
        $str = "DELETE FROM {$table}";
        $this->qb_delete = $str;
        $this->sql_type = 'delete';
        return $this;
    }

    /**csw
     * @param $from  表名称
     * @return $this
     */
    public function from($from)
    {
        if(empty($from))
            $this->addError('101','表名称不能为空！','from');

        $this->qb_from[] = $this->_protect_identifiers($from,true);
        return $this;
    }

    /**保存where的信息
     * @param $where
     * @return $this
     */
    public function where($where)
    {
        if(!is_array($where))
            $this->addError(102,'条件不是数组，直接处理为WHERE 1','where');

        foreach ($where as $key=>$val)
        {
            $arr = $this->_where_connector($val);
            $field = $this->_protect_identifiers($key,true);
            $connection  = $arr[0];
            $val = $arr[1];
            $qb_where[] = trim($field.$connection.$val);
        }
        $this->_wh('where',$qb_where);
        return $this;
    }

    /**csw
     * 保存or_where相关信息
     * @param $or_where
     * @return $this
     */
    public function or_where($or_where)
    {
        foreach ($or_where as $key=>$val)
        {
            $arr = $this->_where_connector($val);
            $field = $this->_protect_identifiers($key,true);
            $connection  = $arr[0];
            $val = $arr[1];
            $qb_or_where[] = trim($field.$connection.$val);
        }

        $this->_wh('or_where',$qb_or_where);
        return $this;
    }

    /**csw
     * 保存where_in相关信息
     * @param $where_in
     * @return $this
     */
    public function where_in($where_in)
    {
        $qb_where_in = array();
        foreach ($where_in as $key=>$val)
        {
            $key = $this->_protect_identifiers($key,true);
            foreach($val as $ke=>$va)
            {
                $val[$ke] = $this->_protect_identifiers($va,false);
            }
            $qb_where_in[$key] = $val;
        }
        $this->_wh('where_in',$qb_where_in);
        return $this;
    }

    /**csw
     * @param $or_where_in
     * @return $this
     */
    public function or_where_in($or_where_in)
    {
        $qb_or_where_in = array();
        foreach ($or_where_in as $key=>$val)
        {
            $key = $this->_protect_identifiers($key,true);
            foreach($val as $ke=>$va)
            {
                $val[$ke] = $this->_protect_identifiers($va,false);
            }
            $qb_or_where_in[$key] = $val;
        }
        $this->_wh('or_where_in',$qb_or_where_in);
        return $this;
    }

    /**csw
     * @param $like
     * @return $this
     */
    public function like($like)
    {
        $this->_like('like',$like);
        return $this;
    }

    /**csw
     * @param $or_like
     * @return $this
     */
    public function or_like($or_like)
    {
        $this->_like('or_like',$or_like);
        return $this;
    }

    private function _like($type,$content)
    {
        $qb_like = array();
        foreach ($content as $key=>$val)
        {
            $field = $this->_protect_identifiers($key,true);
            $val = $this->_protect_identifiers($val,false);
            $qb_like[] = $field.' LIKE '.$val;
        }
        $this->_wh($type,$qb_like);
    }

    /**csw
     * 排序参照字段
     * @param array $order_by
     * @return $this
     */
    public function order_by(array $order_by)
    {
        foreach ($order_by as $key=>$val)
        {
            $field = $this->_protect_identifiers($key,true);
            $val = strtoupper($val);
            $sort = ($val == 'ASC' || $val =='DESC') ? $val : 'ASC';
            $this->qb_order_by[$field] = $sort;
        }
        return $this;
    }

    /**csw
     *获取记录偏移量以及最大记录数
     * @param $offset
     * @param int|string $num
     * @return $this
     */
    public function limit($offset,$num='')
    {
        $offset = empty($num) ? 0 : intval($offset);
        $this->limit = array(
            'offset' => $offset,
            'num'   => intval($num)
        );

        return $this;
    }

    /**csw
     * 分页操作用到
     * @param $perPage 每页多少条
     * @param $pageNum 显示第几页
     * @return $this
     */
    public function page($perPage,$pageNum)
    {
        $offset = ($pageNum-1) * $perPage;
        $this->limit($offset,$perPage);
        return $this;
    }

    /**csw
     * 获取sql语句
     * 自动销毁当前对象并重新返回新的
     */
    public function get()
    {
        $this->_exe();
        echo $this->sql;
        die;
        return $this->sql;
    }

    /**csw
     * 拼接sql语句
     */
    public function _exe()
    {
        switch ($this->sql_type)
        {
            case 'insert'||'replace' :
                break;

            case 'update' :
                $whSql = $this->_wh_str();
                $this->sql = $this->qb_update.' '.$whSql;
                break;

            case 'delete' :
                $whSql = $this->_wh_str();
                $this->sql = $this->qb_delete.' '.$whSql;
                break;

            default :
                $selSql = $this->_select_str();
                $fromSql = $this->_from_str();
                $whSql = $this->_wh_str();
                $orderSql = $this->_order_str();
                $limitSql = $this->_limit_str();
                $this->sql = $selSql.$fromSql.$whSql.$orderSql.$limitSql;
        }
    }

    /**csw
     * 处理where连接符p
     *!= ,>= ext
     * @param $val
     * @return string
     */
    private function _where_connector($val)
    {
        $arr = explode(' ',$val);
        if(count($arr)==1)
        {
            array_unshift($arr,'=');
        }
        $arr[1] = $this->_protect_identifiers($arr[1],false);
        return $arr;
    }

    /**csw
     * 保存所有关于where的信息
     * @param $type
     * @param $content
     */

    private function _wh($type,$content)
    {
        switch ($type)
        {
            case 'where' :
                $this->qb_wh[] = array(
                    'type'      => 'where',
                    'content'   =>  $content,
                );
                break;

            case 'or_where' :
                $this->qb_wh[] = array(
                    'type'      => 'or_where',
                    'content'   =>  $content,
                );
                break;

            case 'where_in' :
                $this->qb_wh[] = array(
                    'type'      =>  'where_in',
                    'content'   =>  $content,
                );
                break;

            case 'or_where_in' :
                $this->qb_wh[] = array(
                     'type'     =>  'or_where_in',
                    'content'   =>  $content,
                );
                break;

            case 'like' :
                $this->qb_wh[] = array(
                    'type'      => 'like',
                    'content'   => $content,
                );
                break;

            case 'or_like' :
                $this->qb_wh[] = array(
                    'type'      =>  'or_like',
                    'content'   =>  $content,
                );
                break ;

            case 'where_not_in' :
                break;

            default :
        }
    }

    /**csw
     * 拼接select字符串
     */
    private function _select_str()
    {
        $sql = implode(',', $this->qb_select);
        $sql = 'SELECT ' . $sql . ' ';
        return $sql;
    }

    /**csw
     * 拼接from字符串
     */
    private function _from_str()
    {
        $sql = implode(',',$this->qb_from);
        $sql = 'FROM '.$sql.' ';
        return $sql;
    }

    /**csw
     * 拼接所有条件相关的字符串
     */
    private function _wh_str()
    {
        if(empty($this->qb_wh))
            return '';

        $sql = 'WHERE ';
        foreach ($this->qb_wh as $key=>$val)
        {
            $type = $val['type'];
            switch ($type)
            {
                case 'where' :
                    $where_sql = $this->_where_str($key,$val['content']);
                    $sql.=$where_sql;
                    break;

                case 'or_where' :
                    $or_where_sql = $this->_or_where_str($key,$val['content']);
                    $sql.=$or_where_sql;
                    break;

                case 'where_in':
                    $where_in_sql = $this->_where_in_str($key,$val['content']);
                    $sql.=$where_in_sql;
                    break;
                case 'or_where_in' :
                    $or_where_in_sql = $this->_or_where_in_str($key,$val['content']);
                    $sql.= $or_where_in_sql;
                    break;
                case 'like' :
                    $like_sql = $this->_like_str($key,$val['content']);
                    $sql.= $like_sql;
                    break;
                case 'or_like' :
                    $or_like_sql = $this->_or_like_str($key,$val['content']);
                    $sql.= $or_like_sql;
                    break;
            }
        }
        return $sql;
    }

    /**csw
     * 拼接where方法相关字符串
     * @param $order        该规则所处的序列位置
     * @param array $arr    规则数组
     * @return string
     */
    private function _where_str($order,array $arr)
    {
        $sql = implode(' AND ',$arr);
        $sql = ($order==0) ? $sql : ' AND '.$sql;
        return $sql;
    }

    /**csw
     * 拼接or_where方法相关字符串
     * @param $order 该规则所处的序列位置
     * @param array $arr 规则数组
     * @return string
     */
    private function _or_where_str($order,array $arr)
    {
        $sql = implode(' AND ',$arr);
        $sql = '('.$sql.')';
        $sql = ($order==0) ? $sql : ' OR '.$sql;
        return $sql;
    }

    /**
     * @param $order       该规则所处的序列位置
     * @param array $arr 规则数组
     * @return string
     */
    private function _where_in_str($order,array $arr)
    {
        $str_arr = array();
        foreach ($arr as $key=>$val)
        {
            $temp = implode(',',$val);
            $temp = '('.$temp.')';
            $str_arr[] = $key.' '.'IN '.$temp;
        }
        $sql = implode(' AND ',$str_arr);
        $sql = ($order==0) ? $sql : ' AND '.$sql;
        return $sql;
    }

    /**csw
     * @param $order
     * @param array $arr
     * @return string
     */
    private function _or_where_in_str($order,array $arr)
    {
        $str_arr = array();
        foreach ($arr as $key=>$val)
        {
            $temp = implode(',',$val);
            $temp = '('.$temp.')';
            $str_arr[] = $key.' '.'IN '.$temp;
        }
        $sql = implode(' AND ',$str_arr);
        $sql = '('.$sql.')';
        $sql = ($order==0) ? $sql : ' OR '.$sql;
        return $sql;
    }

    /**csw
     * @param $order
     * @param array $arr
     * @return string
     */
    public function _like_str($order, array $arr)
    {
        $str_arr = array();
        $sql = implode(' AND ',$arr);
        $sql = ($order==0) ? $sql : ' AND '.$sql;
        return $sql;
    }

    /**csw
     * @param $order
     * @param array $arr
     * @return string
     */
    public function _or_like_str($order,array $arr)
    {
        $str_arr = array();
        $sql = implode(' AND ',$arr);
        $sql = '('.$sql.')';
        $sql = ($order==0) ? $sql : ' OR '.$sql;
        return $sql;
    }

    /**csw
     * 排序呢规则字符串拼接
     */
    private function _order_str()
    {
        if(empty($this->qb_order_by))
            return '';

        $sql = ' ORDER BY ';
        $str_arr = array();
        foreach ($this->qb_order_by as $key=>$val)
        {
            $str_arr[] = $key.' '.$val;
        }
        $sql .= implode(',',$str_arr);
        return $sql;
    }

    /**csw
     * 控制记录获取条目字符串拼接
     */
    private function _limit_str()
    {
        if(empty($this->limit))
            return '';

        $sql = ' Limit ';
        $sql .= $this->limit['offset'].','.$this->limit['num'];
        return $sql;
    }
}