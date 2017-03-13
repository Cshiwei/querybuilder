# querybuilder
一个用来拼接sql语句的工具类，支持select，update，delete，insert，replace等常用sql语句

```php
$qb  = new querybuilder();
$where = array(
            'id'  =>  '5',
            'username'  =>  'lilith',
);
$order = array(
          'id'  =>'ASC',
          'username'=>'DESC',
);
$qb->select('id,username,sex')
      ->from('user')
      ->where($where)
      ->order_by($order)
      ->limit(5,10);
      
  $sql = $qb->get();
  //////////////////插入数据//////////////////
  $data = array(
            'username'  => 'axx',
            'sex'       =>'3',
  );
  $qb->insert('user',$data);
  $sql = $qb->get();
  /////////////更新数据///////////////
  $data = array(
          'username'  =>  'jack',
          'sex'           =>  '1',
  );
  
  $qb->update('user',$data)
        ->...
  $sql = $qb->get();
 ///////////////删除某条数据/////////////
 
 $qb->delete('user')
      ->...
$sql = $qb->get();

//////////////操作方法/////////////
$qb->barch_insert();
$qb->replace();
///////////where条件部分相关方法/////////////
$qb->where();
$qb->where_in();
$qb->or_where();
$qb->or_where_in();
$qb->like();
$qb->or_like();
//////////替换掉limit操作，方便分页//////////////
$qb->page();
 
```
