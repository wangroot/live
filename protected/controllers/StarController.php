<?php

class StarController extends BaseController
{
    public function init()
    {
        Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl.'/css/star.css');
        Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl.'/css/common.css');
    }
    public function actionIndex()
    {

    	$sql = "select * from star_news order by createtime desc limit 7";
    	$key=md5($sql);
    	$newsstar=Yii::app()->cache->get($key);
    	if(empty($newsstar)){
	        $command = Yii::app()->db->createCommand($sql);
	        $newsstar = $command->queryAll();
	        Yii::app()->cache->set($key,$newsstar,300);
    	}
        $criteria = new CDbCriteria();
      
        $criteria->order = 'begintime desc';
        $dataProvider = new CActiveDataProvider('StarSchedule', array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => 10,
                'pageVar' => 'page'
            ),
        ));

        if (Yii::app()->request->isAjaxRequest) {
            $this->layout = 'blank_layout';
            Yii::app()->clientScript->reset();
            $this->render('list_ajax', array('dataProvider' => $dataProvider,'currentPage' => ($dataProvider->pagination->currentPage + 1)));
        } else {

        	$this->render('index',array('newsstar'=>$newsstar,'dataProvider'=>$dataProvider));
    	}
    }

    public function actionInfo()
    {
    	$nowtime=time();
    	
    	$sql = "select * from star_schedule where begintime < '$nowtime' order by lookcount desc limit 6";
    	echo $sql;
    	$command = Yii::app()->db->createCommand($sql);
	    $schedule = $command->queryAll();
	    print_r($schedule);exit;
    	$id = Yii::app()->getRequest()->getParam("id");
		$newsdata=StarSchedule::model()->findByPk($id);
		StarSchedule::model()->updatelook($id);
		$this->render('info',array('newsdata'=>$newsdata));
    }


    public function actionDetail()
    {
    
    	$id = intval(Yii::app()->getRequest()->getParam("id"));//获取明星ID
    	$day = Yii::app()->getRequest()->getParam("day");//获取 
    	if(!empty($day)){
    		$schedule=$this->starschedule($id,$day);//获取明星档数据
    	}else{ 
    		$schedule=$this->starschedule($id,$day);
    	}
    	$getnews=$this->getnews($id);
    	$stardata=Customer::model()->findByPk($id);//获取明星基本资料
		$starinfodata=CustomerInfo::model()->findByAttributes(array('customer_id' => $id));//获取明星详细资料
		$getvideo = $this->getvideos($id);
		/**判断是否关注*/
		if(Yii::app()->user->id){ 
			$isattention= CustomerAttention::model()->isattention(Yii::app()->user->id,$id);
		}else{ 
			$isattention=false;
		}
		/*获取评论数据*/
		
		$criteria = new CDbCriteria(); 
		$criteria->order = 'create_time desc'; 
		$criteria->addCondition('starid='.$id);  
		$criteria->addCondition('type= :type');
		$criteria->params[':type']='starhome';
		$dataProvider=new CActiveDataProvider('Comment',array(
            'criteria'=>$criteria,
            'pagination'=>array(
            'pageSize'=>5,
            ),
        ));
		
        $this->render('detail',array('stardata'=>$stardata,'starinfodata'=>$starinfodata,'schedule'=>$schedule,'getnews'=>$getnews,'getvideo'=>$getvideo,'isattention'=>$isattention,'dataProvider'=>$dataProvider));
    }



    public function starschedule($id,$day){ 
    	$id=3;
    	$year=date('Y');
    	$month=date('m');
    	if($month<12){ 
    		$nmonth=$month+1;
    		$nyear=$year;
    	}else{ 
    		$nmonth=1;
    		$nyear=$year+1;
    	}	
    	$dqtime=$year.'-'.$month.'-1 00:00:00';
    	$nextdqtime=$nyear.'-'.$nmonth.'-1 00:00:00';
    	$dqtime=strtotime($dqtime);
    	$ndqtime=strtotime($nextdqtime);
    	$sql="SELECT id,begintime,title,address,starid FROM `star_schedule` where starid='{$id}' && begintime > '{$dqtime}' && begintime <  '{$ndqtime}' order by begintime asc ";
    	
    	$command = Yii::app()->db->createCommand($sql);
        $scheduleall = $command->queryAll();
        $i=0;
       

        foreach($scheduleall as $v){ 
        	if(!isset($fmonth)){ 
        		$fmonth=intval(date('d',$v['begintime']));
        	}else{ 
        		if($fmonth < intval(date('d',$v['begintime']))){ 
        			$fmonth= intval(date('d',$v['begintime']));
        			$i=0;
        			
        		}

        	}
        	$schedule[$fmonth][$i]=$v;
        	$i++;
        }
        return $schedule;
    }
    public function getnews($star_id){ 
    	$sql="SELECT * FROM `star_news` where star_id='{$star_id}' order by createtime desc LIMIT 6";
    	$command = Yii::app()->db->createCommand($sql);
        $newsall = $command->queryAll();
        return $newsall;

    }
	  public function getvideos($star_id){ 
		  	
    	$sql="SELECT * FROM `product` where customer_id='{$star_id}' && type='video' order by created desc LIMIT 6";
	
    	$command = Yii::app()->db->createCommand($sql);
        $newsall = $command->queryAll();
        return $newsall;

    }
}