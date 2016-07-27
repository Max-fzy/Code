<?php
namespace Admin\Controller;
use Think\Controller;
class GroupUsersController extends CommonController 
{	

	/**
	* @ 删除管理组
	*/
	public function DelGroup()
	{ 
		$id 							= I('get.id');
		$data 							= M('Group');
		$res 							= $data->where('id='.$id)->delete();

		if ($res) { 
			$this->success('删除成功');
		} else { 
			$this->error('删除失败');
		}
	}

	/**
	* @ 编辑管理组
	*/
	public function EditGroup()
	{ 	

		if (IS_POST) { 
			$rule 						= M('Rule');
    		$group 						= M('Group');
    		$gr 						= M('Group_rule');
   			
    		//	接收权限方法ID拼接上逗号。存入数据库
			
    		$title['title'] 			= I('name');
    		$title['rules'] 			= I('id'); 
    					
    		$titl['title'] 				= I('name');
    		$titl['rules'] 				= implode(',',I('id')); 
    
	
    		$res 						= $group->where('title='."'{$title['title']}'")->select();
    
    		//删除关联表中的UID。
    		$list 						= $gr->where('uid='.$res[0]['id'])->delete();
   			
 			//删除原来的数据再执行添加
 			$rules['rules'] = '';
    		$res 						= $group->where('title='."'{$title['title']}'")->save($rules);

    		//将数据插入管理组表中
    		$group->create($titl);
    		$res = $group->where('title='."'{$title['title']}'")->save();


    		$tid 						= $title['title'];
    		//用已添加的组名来获取其ID，以便存入关联表中UID
    		$to 						= $group->where('title=' . "'{$tid}'")->select();
    		//循环插入数据库
    		
    		foreach ($_POST['id'] as $v) {
                $da['uid'] 				= $to[0]['id'];
                $da['group_id']  		= $v;
                $gr->create($da);
    			$res 					= $gr->add();
            }
    		
    		$rule 						   = M('adminUser');
    		$u['id'] 						= $_SESSION['adminUser']['id'];

	     	$res1 						   = $rule->where('jk_admin_user.id='.$u['id'] )->join('jk_group ON jk_admin_user.group = jk_group.title','right')->join('jk_group_rule ON jk_group.id = jk_group_rule.uid','right')->join('jk_rule ON jk_group_rule.group_id = jk_rule.id','right')->select();


        	foreach ($res1 as $k => $v) {
            	$nodelist[$v['name']] 			= $v['name'];
            }

            $_SESSION['nodelist'] 			= $nodelist;


    		if ($res) { 

    			$this->success('添加成功',U('ListGroup'));
    		} else { 
    			$this->error('添加失败');
    		}

		} else { 
			$id 						= I('id');
			$edit 						= D('Group');
			$rule 						= M('Rule');
			$res 						= $edit->where('id='.$id)->select();
			$ress 						= $edit->where('id='.$id)->getdata();

			//查询所有权限规则
			$result 					= $rule->select();
			
			$this->assign('lis',$ress);
			$this->assign('list',$res);
			$this->assign('li',$result);
			$this->display();
		}
	} 

	/**
	* @ 添加管理组
	*/
    public function AddGroup()
    {	
    	if (IS_POST) { 
    		$rule 						= M('Rule');
    		$group 						= M('Group');
    		$gr 						= M('Group_rule');
   
    		//接收权限方法ID拼接上逗号。存入数据库
    		$title['title'] 			= I('name');
    		$title['rules'] 			= I('id');

    		//将数据插入管理组表中
    		$titl['title'] 				= I('name');
    		$titl['rules'] 				= implode(',',I('id'));

    		$ress = $group->where('title='."'$titl[title]'")->select();
    		if($ress){ 
    			 $this->error('管理组名不能重复'); 
    		} else { 
    			$group->create($titl);
    			$res 						= $group->add();
    			$tid 						= $title['title'];
    			
    		//用已添加的组名来获取其ID，以便存入关联表中UID
    			$to 						= $group->where('title=' . "'{$tid}'")->select();
    		}


    		//循环UID，GROUP_ID插入关联表中
     		foreach ($_POST['id'] as $v) {
                $da['uid'] 				= $to[0]['id'];
                $da['group_id']  		= $v;
                $gr->create($da);
    			$res 					= $gr->add();
            }


    		if ($res) { 

    			$this->success('添加成功',U('ListGroup'));
    		} else { 
    			$this->error('添加失败');
    		}

    	} else { 
    		$data 						= M('Rule');
    		$res 						= $data->select();
    		$this->assign('list',$res);
			$this->display();
    	}
    }

	/**
	* @ 浏览管理组
	*/    
	public function ListGroup()
	{ 	

		$select  						= I('get.select');
		$sou     						= I('get.sou');
		//判断输入的内容转换成数字
		if ($sou == '启用') { 
			$su 						= 1;
		} else if ($sou == '禁用') { 
			$su 						= 0;
		} else { 
			$su    						= I('get.sou');
		}
		//判断选择条件
		switch($select){ 
			case 'status':
				$list['status'] 		= array('LIKE','%'.$su.'%');
			break;

			case 'group':
				$list['title'] 			= array('LIKE','%'.$su.'%');
			break;

			default:
				$list 					= '';
			break;
		}

		$rule 							= M('Rule');
		$group 							= D('Group');
		$num							= 1;
	

		$count 							= $group->where($list)->count();
		$Page 							= new \Think\Page($count,4,$_GET);
		$show 							= $Page->show();
		
		$groups 						= $group->where($list)->limit($Page->firstRow.','.$Page->listRows)->getlist();
		//循环处理数据
		foreach($groups as &$g){
			$map['id'] 					= array('IN',$g['rules']);
			$rules 						= $rule->where($map)->select();
			$g['rules'] 				= $rules;
	
			
		}

		$total 							= $Page->totalRows;
	
		//分配数据
		$this->assign('total',$total);	
		$this -> assign('groups',$groups);
		$this -> assign('num',$num);
		$this -> assign('btn',$show);
		$this -> display();
	 
		
	}


	/**
	* @ 添加权限规则
	*/
    public function AddRule()
    {	
    	if (IS_POST) { 

    		$data['name'] 				= I('post.name');
    		$data['title'] 				= I('post.title');
    		$rule 						= M('Rule');
    	

    		$res = $rule->where('name='."'$data[name]'")->select();
    		if($res){ 
    			$this->error('控制器方法不能重复添加');
    		} else { 
	    		$rule->create($data);
				$result 					= $rule->add();    			
    		}

    		if ($result) { 
    			$this->success('添加成功',U('ListRule'));
    		} else { 
    			$this->error('添加失败');
    		}
    	} else { 
			$this->display();
    	}
    }

    /**
	* @ 查看权限规则
	*/
    public function ListRule()
    { 
    	$num							= 1;
		$select  						= I('get.select');
		$sou     						= I('get.sou');
		//判断输入的内容转换成数字
		if ($sou == '启用') { 
			$su 						= 1;
		} else if ($sou == '禁用') { 
			$su 						= 0;
		} else { 
			$su   						= I('get.sou');
		}
		//判断选择条件
		switch ($select) { 
			case 'status':
				$list['status'] 		= array('LIKE','%'.$su.'%');
			break;

			case 'group':
				$list['type'] 			= array('LIKE','%'.$su.'%');
			break;

			case 'name':
				$list['name'] 			= array('LIKE','%'.$su.'%');
			break;

			default:
				$list 					= '';
			break;
		}
	
    	$data 							= D('Rule');
    	$page 							= new \Think\Page($data->where($list)->count(),10);

		//model层处理数据返回
		$result 						= $data->where($list)->limit($page->firstRow.','.$page->listRows)->getlist();
		
		//获取分页按钮
		$btn 							= $page->show();
		$total 							= $page->totalRows;
			
		//分配数据
		$this->assign('total',$total);
		$this->assign('btn', $btn);
		$this->assign('num',$num);
		$this->assign('list',$result);
		$this->display();
    }

    /**
	* @ 修改权限规则
	*/
	public function EditRule()
	{ 
		if (IS_POST) { 
			$id 						= I('post.id');
			$data['name'] 				= I('post.name');
			$data['title'] 				= I('post.title');
			$data['type'] 				= I('post.type') ;
			$data['status'] 			= I('post.status') ;
			$da 						= M('Rule');
			$result 					= $da->where('id='.$id)->save($data);

			if ($result) { 
				$this->success('更新成功',U('ListRule'));
			} else { 
				$this->error('更新失败');
			}
		} else { 
			$id 						= I('get.id');
			$data 						= D('Rule');
			$result 					= $data->where('id='.$id)->getlist();
			$group 						= D('Group');
    		$res 						= $group->field('title')->select();
    		
    		$this->assign('result',$res);
			$this->assign('list',$result);
			$this->display();
		} 
	}

	/**
	* @ 删除权限规则
	*/
	public function DelRule()
	{ 
		$id 							= I('get.id');
		$data 							= M('Rule');
		$res 							= $data->where('id='.$id)->delete();
		if ($res) { 
			$this->success('删除成功');
		} else { 
			$this->error('删除失败');
		}
	}
}
