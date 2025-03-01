<?php
/*
 |--------------------------------------------------------------------------
 | SkyCaiji (蓝天采集器)
 |--------------------------------------------------------------------------
 | Copyright (c) 2018 https://www.skycaiji.com All rights reserved.
 |--------------------------------------------------------------------------
 | 使用协议  https://www.skycaiji.com/licenses
 |--------------------------------------------------------------------------
 */

namespace skycaiji\admin\event;
class CpatternEvent extends CpatternColl{
    /**
     * 规则匹配，$field_params传入规则参数
     * @param array $field_params
     * @param string $html
     * @return string
     */
    public function field_module_rule($field_params,$html){
        if(!empty($field_params['rule_multi'])&&'loop'==$field_params['rule_multi_type']){
            
            if(empty($this->first_loop_field)){
                
                $this->first_loop_field=$field_params['name'];
            }
        }
        
        $val = $this->get_rule_module_rule_data(array(
            'rule' => $field_params['reg_rule'],
            'rule_merge' => $field_params['reg_rule_merge'],
            'rule_multi' => $field_params['rule_multi'],
            'rule_multi_str' => $field_params['rule_multi_str'],
            'rule_multi_type' => $field_params['rule_multi_type']
        ), $html,array(),true);
        
        return $val;
    }
    /**
     * xpath规则，$field_params传入规则参数
     * @param array $field_params
     * @param string $html
     * @return string
     */
    public function field_module_xpath($field_params,$html){
        if(!empty($field_params['xpath_multi'])){
            
            if('loop'==$field_params['xpath_multi_type']){
                
                if(empty($this->first_loop_field)){
                    
                    $this->first_loop_field=$field_params['name'];
                }
            }
        }
        return $this->rule_module_xpath_data($field_params,$html);
    }
    /**
     * json提取，$field_params传入规则参数
     * @param array $field_params
     * @param string $html
     * @return string
     */
    private $cache_json_list=array();
    public function field_module_json($field_params,$html,$cur_url=''){
        $jsonKey=!empty($cur_url)?md5($cur_url):md5($html);
        if(!isset($this->cache_json_list[$jsonKey])){
            $this->cache_json_list[$jsonKey]=\util\Funcs::convert_html2json($html);
        }
        $jsonArrType=$field_params['json_arr'];
        if($field_params['json_loop']){
            
            $field_params['json_arr']='_original_';
        }
        $val=$this->rule_module_json_data($field_params,$this->cache_json_list[$jsonKey]);
        if($field_params['json_loop']){
            
            if(is_array($val)){
                $field_params['json_arr']=$jsonArrType;
                foreach ($val as $k=>$v){
                    $val[$k]=$this->rule_module_json_data_convert($v,$field_params);
                }
                
                if(empty($this->first_loop_field)){
                    
                    $this->first_loop_field=$field_params['name'];
                }
            }
        }
        return $val;
    }
    /*字段提取内容*/
    public function field_module_extract($field_params,$extract_field_val,$base_url,$domain_url){
        $field_html=$extract_field_val['value'];
        if(empty($field_html)){
            return '';
        }
        $val='';
        $extract_module=strtolower($field_params['extract_module']);
        switch ($extract_module){
            case 'cover':
                
                if(!empty($extract_field_val['img'])){
                    $val=reset($extract_field_val['img']);
                }else{
                    if(preg_match('/<img\b[^<>]*\bsrc\s*=\s*[\'\"](?P<url>[^\'\"]+?)[\'\"]/i',$field_html,$cover)){
                        $cover=$cover['url'];
                        $cover=$this->create_complete_url($cover, $base_url, $domain_url);
                        $val=$cover;
                    }
                }
                break;
            case 'phone':
                $field_html=$this->filter_html_tags($field_html,'style,script,object');
                $field_html=strip_tags($field_html);
                if(preg_match('/\d{11}/', $field_html,$phone)){
                    $val=$phone[0];
                }
                break;
            case 'email':
                $field_html=$this->filter_html_tags($field_html,'style,script,object');
                $field_html=strip_tags($field_html);
                if(preg_match('/[\w\-]+\@[\w\-\.]+/i', $field_html,$email)){
                    $val=$email[0];
                }
                break;
            case 'rule':
                
                $val = $this->field_module_rule(array(
                'reg_rule'=>$field_params['reg_extract_rule'],
                'reg_rule_merge'=>$field_params['reg_extract_rule_merge'],
                'rule_multi'=>$field_params['extract_rule_multi'],
                'rule_multi_str'=>$field_params['extract_rule_multi_str'],
                ), $field_html);
                
                break;
            case 'xpath':
                $val = $this->field_module_xpath(array(
                'xpath' => $field_params['extract_xpath'],
                'xpath_attr' => $field_params['extract_xpath_attr'],
                'xpath_attr_custom' => $field_params['extract_xpath_attr_custom'],
                'xpath_multi' => $field_params['extract_xpath_multi'],
                'xpath_multi_str' => $field_params['extract_xpath_multi_str'],
                ), $field_html);
                break;
            case 'json':
                $val=$this->field_module_json(array('json'=>$field_params['extract_json'],'json_arr'=>$field_params['extract_json_arr'],'json_arr_implode'=>$field_params['extract_json_arr_implode']), $field_html);
                break;
        }
        return $val;
    }
    
    /*[内容]标签*/
    public function field_module_sign($field_params,$cont_url){
        $val='';
        $urlMd5=md5($cont_url);
        
        list($pageType,$pageName)=$this->page_source_split($field_params['source']);
        if(empty($pageType)){
            $pageType='url';
        }
        
        if(!empty($field_params['sign'])){
            $urlMatches=null;
            $areaMatches=null;
            $contentMatches=$this->get_page_content_match($pageType,$pageName);
            if(!$this->page_rule_is_null($pageType)){
                if(!empty($this->page_url_matches[$pageType])){
                    if($pageType=='url'){
                        
                        $urlMatches=$this->get_page_url_match($pageType,$pageName,$urlMd5);
                    }elseif($pageType=='level_url'){
                        
                        if(!empty($this->cur_level_urls[$pageName])){
                            $urlMatches=$this->get_page_url_match($pageType,$pageName,md5($this->cur_level_urls[$pageName]));
                        }else{
                            $urlMatches=null;
                        }
                    }else{
                        
                        $urlMatches=$this->get_page_url_match($pageType,$pageName);
                    }
                }
                $areaMatches=$this->get_page_area_match($pageType,$pageName);
            }
            if(!is_array($urlMatches)){
                $urlMatches=array();
            }
            if(!is_array($areaMatches)){
                $areaMatches=array();
            }
            if(!is_array($contentMatches)){
                $contentMatches=array();
            }
            
            if(empty($urlMatches)){
                
                $pageSource=$this->page_source_merge($pageType,$pageName);
                $urlSigns=$this->config_params['signs'][$pageSource]['url']['cur']['url'];
                
                $urlMatches=array();
                if($urlSigns&&is_array($urlSigns)){
                    foreach ($urlSigns as $k=>$v){
                        $urlMatches['match'.$v['id']]='';
                    }
                }
            }
            $urlMatches=array_merge($areaMatches,$urlMatches);
            $contentMatches=array_merge($urlMatches,$contentMatches);
            $val=$this->merge_match_signs($contentMatches, $field_params['sign']);
        }
        return $val;
    }
    /*自动获取*/
    public function field_module_auto($field_params,$htmlInfo,$cur_url){
        $html=$htmlInfo['html'];
        switch (strtolower($field_params['auto'])){
            case 'title':$val=\util\HtmlParse::getTitle($html);break;
            case 'content':$val=\util\HtmlParse::getContent($html);break;
            case 'keywords':$val=\util\HtmlParse::getKeywords($html);break;
            case 'description':$val=\util\HtmlParse::getDescription($html);break;
            case 'url':$val=$cur_url;break;
            case 'header':$val=trim($htmlInfo['header']);break;
            case 'html':$val=$html;break;
        }
        return $val;
    }
    public function field_module_words($field_params){
        
        return $field_params['words'];
    }
    public function field_module_num($field_params){
        
        $start=intval($field_params['num_start']);
        $end=intval($field_params['num_end']);
        return rand($start, $end);
    }
    public function field_module_time($field_params){
        $val='';
        $nowTime=time();
        $start=empty($field_params['time_start'])?$nowTime:strtotime($field_params['time_start']);
        $end=empty($field_params['time_end'])?$nowTime:strtotime($field_params['time_end']);
        $time=rand($start, $end);
        if(empty($field_params['time_stamp'])){
            
            $fmt=empty($field_params['time_format'])?'Y-m-d H:i':
            str_replace(array('[年]','[月]','[日]','[时]','[分]','[秒]'), array('Y','m','d','H','i','s'), $field_params['time_format']);
            $val=date($fmt,$time);
        }else{
            $val=$time;
        }
        return $val;
    }
    public function field_module_list($field_params){
        static $list=array();
        $key=md5($field_params['list']);
        if(!isset($list[$key])){
            
            if(preg_match_all('/[^\r\n]+/', $field_params['list'],$str_list)){
                $str_list=$str_list[0];
            }else{
                $str_list=array();
            }
            $list[$key]=$str_list;
        }
        $str_list=$list[$key];
        $val='';
        if(!empty($str_list)){
            $randi=array_rand($str_list,1);
            $val=$str_list[$randi];
        }
        return $val;
    }
    public function field_module_merge($field_params,$val_list){
        $val='';
        
        if(preg_match_all('/\[\x{5b57}\x{6bb5}\:(.+?)\]/u', $field_params['merge'],$match_fields)){
            $val=$field_params['merge'];
            
            for($i=0;$i<count($match_fields[0]);$i++){
                $field=$match_fields[1][$i];
                if(is_array($val_list[$field])&&isset($val_list[$field]['value'])){
                    $val=str_replace($match_fields[0][$i],$val_list[$field]['value'],$val);
                }
            }
        }
        return $val;
    }
    
    /*数据处理方法*/
    public function process_f_html($fieldVal,$params){
        $htmlAllow=array_filter(explode(',',$params['html_allow']));
        $htmlFilter=array_filter(explode(',',$params['html_filter']));
        if(!empty($htmlAllow)){
            
            $htmlAllowStr='';
            foreach ($htmlAllow as $v){
                $htmlAllowStr.='<'.$v.'>';
            }
            $fieldVal=strip_tags($fieldVal,$htmlAllowStr);
        }
        if(!empty($htmlFilter)){
            
            if(in_array('all', $htmlFilter)){
                
                $fieldVal=$this->filter_html_tags($fieldVal, array('style','script','object'));
                $fieldVal=strip_tags($fieldVal);
            }else{
                $fieldVal=$this->filter_html_tags($fieldVal, $htmlFilter);
            }
        }
        return $fieldVal;
    }
    public function process_f_insert($fieldVal,$params){
        $txt=$params['insert_txt'];
        if(empty($params['insert_loc'])){
            $fieldVal.=$txt;
        }elseif($params['insert_loc']=='head'){
            $fieldVal=$txt.$fieldVal;
        }elseif($params['insert_loc']=='rand'){
            $pattern='/<(?:p|br)[^<>]*>/i';
            if(preg_match_all($pattern,$fieldVal,$matches)){
                $count=count($matches[0]);
                $rand=rand(0,$count-1);
                $index=0;
                $fieldVal=preg_replace_callback($pattern, function($match)use($txt,$rand,&$index){
                    $val=$match[0];
                    if($index==$rand){
                        
                        $val.=$txt;
                    }
                    $index++;
                    return $val;
                }, $fieldVal);
            }else{
                $rand=rand(0,1);
                if($rand){
                    
                    $fieldVal=$txt.$fieldVal;
                }else{
                    $fieldVal.=$txt;
                }
            }
        }
        return $fieldVal;
    }
    public function process_f_replace($fieldVal,$params){
        return preg_replace('/'.$params['replace_from'].'/ui',$params['replace_to'], $fieldVal);
    }
    public function process_f_tool($fieldVal,$params){
        
        if(in_array('format', $params['tool_list'])){
            
            $fieldVal=$this->filter_html_tags($fieldVal,array('style','script'));
            $fieldVal=preg_replace('/\b(id|class|style|width|height|align)\s*=\s*([\'\"])[^\<\>\'\"]+?\\2(?=\s|$|\/|>)/i', ' ', $fieldVal);
        }
        if(in_array('trim', $params['tool_list'])){
            
            $fieldVal=trim($fieldVal);
        }
        if(in_array('is_img', $params['tool_list'])){
            
            if(!is_empty(g_sc_c('download_img','download_img'))){
                
                $fieldVal=preg_replace('/(?<![\'\"])(\bhttp[s]{0,1}\:\/\/[^\s\'\"\<\>]+)(?![\'\"])/i','{[img]}'."$1".'{[/img]}',$fieldVal);
            }
        }
        return $fieldVal;
    }
    public function process_f_translate($fieldVal,$params,$fieldName=''){
        
        static $regEmpty='/^([\s\r\n]|\&nbsp\;)*$/';
        if(!is_empty(g_sc_c('translate'))&&!is_empty(g_sc_c('translate','open'))&&!empty($fieldVal)){
            
            if($this->is_collecting()){
                $this->echo_msg(array('正在翻译：%s',$fieldName),'black',true,'','display:inline;margin-right:5px;');
            }
            
            $langFrom=$params['translate_from']=='custom'?$params['translate_from_custom']:$params['translate_from'];
            $langTo=$params['translate_to']=='custom'?$params['translate_to_custom']:$params['translate_to'];
            
            if(!is_empty(g_sc_c('translate','pass_html'))){
                
                $htmlMd5List=array();
                $txtMd5List=array();
                
                
                static $tagRegs=array('/<\![\s\S]*?>/','/<(script|style)[^\r\n]*?>[\s\S]*?<\/\1>/i','/<[\/]*\w+\b[^\r\n]*?>/');
                foreach($tagRegs as $tagReg){
                    $fieldVal=preg_replace_callback($tagReg,function($mhtml)use(&$htmlMd5List){
                        $key='{'.md5($mhtml[0]).'}';
                        $htmlMd5List[$key]=$mhtml[0];
                        return $key;
                    },$fieldVal);
                }
                
                if(empty($htmlMd5List)){
                    
                    if(!empty($fieldVal)&&!preg_match($regEmpty, $fieldVal)){
                        
                        $fieldVal=$this->execute_translate($fieldVal, $langFrom, $langTo);
                    }
                }else{
                    
                    
                    $fieldVal=preg_replace_callback('/([\s\S]*?)(\{[a-zA-Z0-9]{32}\})/i',function($mtxt)use(&$txtMd5List){
                        $key='['.md5($mtxt[1]).']';
                        $txtMd5List[$key]=$mtxt[1];
                        return $key.$mtxt[2];
                    },$fieldVal);
                        
                        foreach ($txtMd5List as $k=>$v){
                            
                            if(!empty($v)&&!preg_match($regEmpty, $v)){
                                
                                $txtMd5List[$k]=$this->execute_translate($v, $langFrom, $langTo);
                            }
                        }
                        
                        if(!empty($txtMd5List)){
                            $fieldVal=str_replace(array_keys($txtMd5List), $txtMd5List, $fieldVal);
                        }
                        
                        if(!empty($htmlMd5List)){
                            $fieldVal=str_replace(array_keys($htmlMd5List), $htmlMd5List, $fieldVal);
                        }
                }
            }else{
                
                if(!empty($fieldVal)&&!preg_match($regEmpty, $fieldVal)){
                    
                    $fieldVal=$this->execute_translate($fieldVal, $langFrom, $langTo);
                }
            }
        }
        return $fieldVal;
    }
    
    public function process_f_batch($fieldVal,$params){
        
        static $batch_list=array();
        if(!empty($params['batch_list'])){
            $listMd5=md5($params['batch_list']);
            if(!isset($batch_list[$listMd5])){
                
                if(preg_match_all('/([^\r\n]+?)\=([^\r\n]+)/', $params['batch_list'],$mlist)){
                    $batch_re=$mlist[1];
                    $batch_to=$mlist[2];
                    $batch_list[$listMd5]=array($batch_re,$batch_to);
                }
            }else{
                $batch_re=$batch_list[$listMd5][0];
                $batch_to=$batch_list[$listMd5][1];
            }
            $batch_re=is_array($batch_re)?$batch_re:array();
            $batch_to=is_array($batch_to)?$batch_to:array();
            if(!empty($batch_re)&&count($batch_re)==count($batch_to)){
                
                $fieldVal=str_replace($batch_re, $batch_to, $fieldVal);
            }
        }
        return $fieldVal;
    }
    public function process_f_substr($fieldVal,$params){
        $params['substr_len']=intval($params['substr_len']);
        if($params['substr_len']>0){
            if(mb_strlen($fieldVal,'utf-8')>$params['substr_len']){
                
                $fieldVal=mb_substr($fieldVal,0,$params['substr_len'],'utf-8').$params['substr_end'];
            }
        }
        return $fieldVal;
    }
    public function process_f_func($fieldVal,$params,$curUrlMd5,$loopIndex,$contUrlMd5,$fieldName=''){
        
        $result=$this->execute_plugin_func('process', $params['func_name'], $fieldVal, $params['func_param'], $this->_get_insert_fields($params['func_param'], $curUrlMd5, $loopIndex));
        if(isset($result)){
            $fieldVal=$result;
        }
        return $fieldVal;
    }
    public function process_f_filter($fieldVal,$params,$curUrlMd5,$loopIndex,$contUrlMd5,$fieldName=''){
        static $key_list=array();
        if(!empty($params['filter_list'])){
            $listMd5=md5($params['filter_list']);
            if(!isset($key_list[$listMd5])){
                $filterList=explode("\r\n", $params['filter_list']);
                $filterList=array_filter($filterList);
                $key_list[$listMd5]=$filterList;
            }else{
                $filterList=$key_list[$listMd5];
            }
            $filterList=is_array($filterList)?$filterList:array();
            
            
            if(!empty($params['filter_pass'])){
                if($params['filter_pass']=='1'){
                    
                    foreach ($filterList as $filterStr){
                        if(stripos($fieldVal,$filterStr)!==false){
                            
                            $fieldVal='';
                            break;
                        }
                    }
                }elseif($params['filter_pass']=='2'){
                    
                    foreach ($filterList as $filterStr){
                        if(stripos($fieldVal,$filterStr)!==false){
                            
                            if(!isset($this->exclude_cont_urls[$contUrlMd5])){
                                $this->exclude_cont_urls[$contUrlMd5]=array();
                            }
                            
                            if(empty($this->first_loop_field)){
                                
                                $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5]=json_encode(array('field'=>$fieldName,'type'=>'filter','filter'=>$filterStr));
                            }else{
                                
                                if(!isset($this->exclude_cont_urls[$contUrlMd5][$curUrlMd5])){
                                    $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5]=array();
                                }
                                $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5][$loopIndex]=json_encode(array('field'=>$fieldName,'type'=>'filter','filter'=>$filterStr));
                            }
                            break;
                        }
                    }
                }elseif($params['filter_pass']=='3'){
                    
                    $hasKey=false;
                    foreach ($filterList as $filterStr){
                        if(stripos($fieldVal,$filterStr)!==false){
                            
                            $hasKey=true;
                            break;
                        }
                    }
                    if(!$hasKey){
                        $fieldVal='';
                    }
                }elseif($params['filter_pass']=='4'){
                    
                    $hasKey=false;
                    foreach ($filterList as $filterStr){
                        if(stripos($fieldVal,$filterStr)!==false){
                            
                            $hasKey=true;
                            break;
                        }
                    }
                    if(!$hasKey){
                        
                        if(!isset($this->exclude_cont_urls[$contUrlMd5])){
                            $this->exclude_cont_urls[$contUrlMd5]=array();
                        }
                        
                        if(empty($this->first_loop_field)){
                            
                            $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5]=json_encode(array('field'=>$fieldName,'type'=>'filter','filter'=>''));
                        }else{
                            
                            if(!isset($this->exclude_cont_urls[$contUrlMd5][$curUrlMd5])){
                                $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5]=array();
                            }
                            $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5][$loopIndex]=json_encode(array('field'=>$fieldName,'type'=>'filter','filter'=>''));
                        }
                    }
                }
            }else{
                
                $fieldVal=str_ireplace($filterList, $params['filter_replace'], $fieldVal);
            }
        }
        return $fieldVal;
    }
    public function process_f_if($fieldVal,$params,$curUrlMd5,$loopIndex,$contUrlMd5,$fieldName=''){
        static $func_list=array();
        
        if(is_array($params['if_logic'])&&!empty($params['if_logic'])){
            
            $ifOrList=array();
            $ifAndList=array();
            
            foreach($params['if_logic'] as $ifk=>$iflv){
                if('or'==$iflv){
                    if(!empty($ifAndList)){
                        
                        $ifOrList[]=$ifAndList;
                    }
                    $ifAndList=array();
                    $ifAndList[]=$ifk;
                }elseif('and'==$iflv){
                    
                    $ifAndList[]=$ifk;
                }
            }
            if(!empty($ifAndList)){
                
                $ifOrList[]=$ifAndList;
            }
            if(is_array($ifOrList)&&!empty($ifOrList)){
                $isTrue=false;
                $breakCond='';
                
                foreach ($ifOrList as $ifAndList){
                    $ifAndResult=true;
                    foreach ($ifAndList as $ifIndex){
                        $ifLogic=$params['if_logic'][$ifIndex];
                        $ifCond=$params['if_cond'][$ifIndex];
                        if(empty($ifLogic)||empty($ifCond)){
                            
                            continue;
                        }
                        $ifVal=$params['if_val'][$ifIndex];
                        $result=false;
                        $breakCond=lang('p_m_if_c_'.$ifCond).':'.$ifVal;
                        switch($ifCond){
                            case 'regexp':
                                if(preg_match('/'.$ifVal.'/'.$this->config['reg_regexp_flags'], $fieldVal)){
                                    $result=true;
                                }
                                break;
                            case 'func':
                                $funcName=$params['if_addon']['func'][$ifIndex];
                                $isTurn=$params['if_addon']['turn'][$ifIndex];
                                $isTurn=$isTurn?true:false;
                                $result=$this->execute_plugin_func('processIf', $funcName, $fieldVal, $ifVal, $this->_get_insert_fields($ifVal, $curUrlMd5, $loopIndex));
                                $result=$result?true:false;
                                if($isTurn){
                                    $result=$result?false:true;
                                }
                                $breakCond=lang('p_m_if_c_'.$ifCond).':'.$funcName.($isTurn?'取反':'');
                                break;
                            case 'has':$result=stripos($fieldVal,$ifVal)!==false?true:false;break;
                            case 'nhas':$result=stripos($fieldVal,$ifVal)===false?true:false;break;
                            case 'eq':$result=$fieldVal==$ifVal?true:false;break;
                            case 'neq':$result=$fieldVal!=$ifVal?true:false;break;
                            case 'heq':$result=$fieldVal===$ifVal?true:false;break;
                            case 'nheq':$result=$fieldVal!==$ifVal?true:false;break;
                            case 'gt':$result=$fieldVal>$ifVal?true:false;break;
                            case 'egt':$result=$fieldVal>=$ifVal?true:false;break;
                            case 'lt':$result=$fieldVal<$ifVal?true:false;break;
                            case 'elt':$result=$fieldVal<=$ifVal?true:false;break;
                            case 'time_eq':
                            case 'time_egt':
                            case 'time_elt':
                                $fieldTime=is_numeric($fieldVal)?$fieldVal:strtotime($fieldVal);
                                $valTime=is_numeric($ifVal)?$ifVal:strtotime($ifVal);
                                if($ifCond=='time_eq'){
                                    
                                    $result=$fieldTime==$valTime?true:false;
                                }elseif($ifCond=='time_egt'){
                                    
                                    $result=$fieldTime>=$valTime?true:false;
                                }elseif($ifCond=='time_elt'){
                                    
                                    $result=$fieldTime<=$valTime?true:false;
                                }
                                break;
                        }
                        if(!$result){
                            
                            $ifAndResult=false;
                            break;
                        }
                    }
                    
                    if($ifAndResult){
                        
                        $isTrue=true;
                        break;
                    }
                }
                
                $exclude=null;
                
                switch ($params['if_type']){
                    case '1':$exclude=$isTrue?null:array('if'=>'1');break;
                    case '2':$exclude=$isTrue?array('if'=>'2'):null;break;
                    case '3':$exclude=!$isTrue?null:array('if'=>'3');break;
                    case '4':$exclude=!$isTrue?array('if'=>'4'):null;break;
                }
                
                if(!empty($exclude)){
                    $exclude['type']='if';
                    $exclude['field']=$fieldName;
                    $exclude['cond']=$breakCond;
                    $exclude=json_encode($exclude);
                    
                    if(!isset($this->exclude_cont_urls[$contUrlMd5])){
                        $this->exclude_cont_urls[$contUrlMd5]=array();
                    }
                    
                    if(empty($this->first_loop_field)){
                        
                        $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5]=$exclude;
                    }else{
                        
                        if(!isset($this->exclude_cont_urls[$contUrlMd5][$curUrlMd5])){
                            $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5]=array();
                        }
                        $this->exclude_cont_urls[$contUrlMd5][$curUrlMd5][$loopIndex]=$exclude;
                    }
                }
            }
        }
        return $fieldVal;
    }
    /*调用接口*/
    public function process_f_api($fieldVal,$params,$curUrlMd5,$loopIndex,$contUrlMd5,$fieldName=''){
        static $retryCur=0;
        $retryMax=intval($params['api_retry']);
        $retryParams=null;
        if($retryMax>0){
            
            $retryParams=array(0=>$fieldVal,1=>$params,2=>$curUrlMd5,3=>$loopIndex,4=>$contUrlMd5,5=>$fieldName);
        }
        
        $url=$params['api_url'];
        $result=null;
        if(!empty($url)){
            $isLoc=false;
            if(!preg_match('/^\w+\:\/\//', $url)&&strpos($url, '/')===0){
                
                $isLoc=true;
                $url=config('root_website').$url;
            }
            if(preg_match('/^\w+\:\/\//', $url)){
                
                
                
                $charset=$params['api_charset'];
                if($charset=='custom'){
                    $charset=$params['api_charset_custom'];
                }
                if(empty($charset)){
                    $charset='utf-8';
                }
                
                
                $postData=array();
                if(is_array($params['api_params'])){
                    init_array($params['api_params']['name']);
                    init_array($params['api_params']['val']);
                    init_array($params['api_params']['addon']);
                    foreach ($params['api_params']['name'] as $k=>$v){
                        if(empty($v)){
                            continue;
                        }
                        $val=$params['api_params']['val'][$k];
                        $addon=$params['api_params']['addon'][$k];
                        switch ($val){
                            case 'field':$val=$fieldVal;break;
                            case 'timestamp':$val=time();break;
                            case 'time':$addon=$addon?$addon:'Y-m-d H:i:s';$val=date($addon,time());break;
                            case 'custom':$val=$this->_replace_insert_fields($addon, $curUrlMd5, $loopIndex);break;
                        }
                        $postData[$v]=$val;
                    }
                }
                
                $headers=array();
                if(is_array($params['api_headers'])){
                    init_array($params['api_headers']['name']);
                    init_array($params['api_headers']['val']);
                    init_array($params['api_headers']['addon']);
                    foreach ($params['api_headers']['name'] as $k=>$v){
                        if(empty($v)){
                            continue;
                        }
                        $val=$params['api_headers']['val'][$k];
                        $addon=$params['api_headers']['addon'][$k];
                        switch ($val){
                            case 'field':$val=$fieldVal;break;
                            case 'timestamp':$val=time();break;
                            case 'time':$addon=$addon?$addon:'Y-m-d H:i:s';$val=date($addon,time());break;
                            case 'custom':$val=$this->_replace_insert_fields($addon, $curUrlMd5, $loopIndex);break;
                        }
                        $headers[$v]=$val;
                    }
                }
                
                
                if($params['api_content_type']){
                    $headers['content-type']=$params['api_content_type'];
                }
                
                if($params['api_type']=='post'){
                    
                    $postData=empty($postData)?true:$postData;
                }else{
                    
                    $url=\util\Funcs::url_params_charset($url,$postData,$charset);
                    $postData=null;
                }
                
                $result=get_html($url,$headers,array(),$charset,$postData,true);
                $this->collect_sleep($params['api_interval'],true);
                if(!empty($result['ok'])){
                    
                    $retryCur=0;
                    $fieldVal=$this->rule_module_json_data(array('json'=>$params['api_json'],'json_arr'=>$params['api_json_arr'],'json_arr_implode'=>$params['api_json_implode']),$result['html']);
                }else{
                    if($retryMax<=0||($retryCur<=0&&$this->is_collecting())){
                        
                        $urlEcho=htmlspecialchars($url);
                        $echoMsg='<div class="clear"><span class="left">数据处理»调用接口失败：</span><a href="'.$urlEcho.'" target="_blank" class="lurl">'.$urlEcho.'</a></div>';
                        if(!$this->is_collecting()){
                            $echoMsg=strip_tags($echoMsg);
                        }
                        $this->echo_error($echoMsg);
                    }
                    
                    $this->collect_sleep($params['api_wait']);
                    
                    if($retryMax>0&&is_array($retryParams)){
                        
                        if($retryCur<$retryMax){
                            
                            $retryCur++;
                            if($this->is_collecting()){
                                $this->echo_msg(array('%s第%s次',$retryCur>1?' / ':'重试：',$retryCur),'black',true,'','display:inline;'.($retryCur==$retryMax?'margin-right:5px;':''));
                            }
                            return $this->process_f_api($retryParams[0],$retryParams[1],$retryParams[2],$retryParams[3],$retryParams[4],$retryParams[5]);
                        }else{
                            $retryCur=0;
                            if($this->is_collecting()){
                                $this->echo_msg('接口无效','red',true,'','display:inline;margin-right:5px;');
                            }else{
                                
                                $this->echo_error('数据处理»调用接口：'.htmlspecialchars($url).'，已重试'.$retryMax.'次，接口无效 ');
                            }
                        }
                    }
                }
            }
        }
        return $fieldVal;
    }
    /*数据处理*/
    public function process_field($fieldName,$fieldVal,$process,$curUrlMd5,$loopIndex,$contUrlMd5){
        if(empty($process)){
            return $fieldVal;
        }
        static $conds=array('filter','if','func','api');
        foreach ($process as $params){
            
            if(empty($this->first_loop_field)){
                
                if(isset($this->exclude_cont_urls[$contUrlMd5][$curUrlMd5])){
                    return $fieldVal;
                }
            }else{
                
                if(isset($this->exclude_cont_urls[$contUrlMd5][$curUrlMd5][$loopIndex])){
                    return $fieldVal;
                }
            }
            $funcName='process_f_'.$params['module'];
            if(method_exists($this, $funcName)){
                if(in_array($params['module'],$conds)){
                    
                    $fieldVal=$this->$funcName($fieldVal,$params,$curUrlMd5,$loopIndex,$contUrlMd5,$fieldName);
                }elseif($params['module']=='translate'){
                    $fieldVal=$this->$funcName($fieldVal,$params,$fieldName);
                }else{
                    $fieldVal=$this->$funcName($fieldVal,$params);
                }
            }
        }
        return $fieldVal;
    }
    
    /**
     * 转换起始网址
     * @param string $url
     * @return multitype:mixed |unknown
     */
    public function source_url_convert($url){
        $urls=array();
        $parentMatches=$this->parent_page_signs2matches($this->parent_page_signs('source_url','','url'));
        $url=$this->merge_match_signs($parentMatches, $url);
        if(preg_match('/\{param\:(?P<type>[a-z]+)\,(?P<val>.*?)\}/i', $url,$match)){
            
            $fmtUrl=preg_replace('/\{param\:.*?\}/i', '__set:param__', $url);
            $type=strtolower($match['type']);
            $val=explode("\t", $match['val']);
            if($type=='num'){
                
                $num_start = intval($val[0]);
                $num_end = intval($val[1]);
                $num_end = max ($num_start,$num_end);
                $num_inc = max ( 1, intval($val[2]));
                $num_desc =$val[3]?1:0;
                
                if($num_desc){
                    
                    for($i=$num_end;$i>=$num_start;$i--){
                        $urls[]=str_replace('__set:param__', $num_start+($i-$num_start)*$num_inc, $fmtUrl);
                    }
                }else{
                    for($i=$num_start;$i<=$num_end;$i++){
                        $urls[]=str_replace('__set:param__', $num_start+($i-$num_start)*$num_inc, $fmtUrl);
                    }
                }
            }elseif($type=='letter'){
                
                $letter_start=ord($val[0]);
                $letter_end=ord($val[1]);
                $letter_end=max($letter_start,$letter_end);
                $letter_desc=$val[2]?1:0;
                
                if($letter_desc){
                    
                    for($i=$letter_end;$i>=$letter_start;$i--) {
                        $urls[]=str_replace('__set:param__', chr($i), $fmtUrl);
                    }
                }else{
                    for($i=$letter_start;$i<=$letter_end;$i++) {
                        $urls[]=str_replace('__set:param__', chr($i), $fmtUrl);
                    }
                }
            }elseif($type=='custom'){
                
                foreach ($val as $v){
                    $urls[]=str_replace('__set:param__', $v, $fmtUrl);
                }
            }
            return $urls;
        }if(preg_match('/\{json\:([^\}]*)\}/i',$url,$match)){
            
            $url=preg_replace('/\{json\:([^\}]*)\}/i','',$url);
            $jsonRule=trim($match[1]);
            if(is_null($jsonRule)||$jsonRule==''){
                $jsonRule='*';
            }
            $jsonData=$this->get_html($url);
            if(!empty($jsonData)){
                
                $urls=$this->rule_module_json_data(array('json'=>$jsonRule,'json_arr'=>'_original_'),$jsonData);
                if(empty($urls)){
                    $urls=array();
                }
                if(!is_array($urls)){
                    $urls=array($urls);
                }
                
                foreach ($urls as $k=>$v){
                    if(!is_string($v)||!preg_match('/^\w+\:\/\//i', $v)){
                        
                        unset($urls[$k]);
                    }
                }
                if(!empty($urls)&&is_array($urls)){
                    $urls=array_unique($urls);
                    $urls=array_values($urls);
                }
                return $urls;
            }
        }elseif(preg_match('/[\r\n]/', $url)){
            
            if(preg_match_all('/^\w+\:\/\/[^\r\n]+/im',$url,$urls)){
                
                $urls=array_unique($urls[0]);
                $urls=array_values($urls);
            }else{
                $urls=array();
            }
            return $urls;
        }else{
            
            return $url;
        }
    }
    
    private function _get_insert_fields($paramsStr,$curUrlMd5,$loopIndex){
        $fieldRule='/\[\x{5b57}\x{6bb5}\:(.+?)\]/u';
        $fields=array();
        if($paramsStr){
            
            $fields=\util\Funcs::txt_match_params($paramsStr,$fieldRule,1);
        }
        init_array($fields);
        $fieldVals=array();
        if(!empty($fields)){
            if(empty($this->first_loop_field)){
                
                foreach ($fields as $field){
                    if(is_array($this->field_val_list[$field])){
                        $fieldVals['[字段:'.$field.']']=$this->field_val_list[$field]['values'][$curUrlMd5];
                    }
                }
            }else{
                
                foreach ($fields as $field){
                    $fieldVal=$this->field_val_list[$field];
                    if(is_array($fieldVal)){
                        $fieldVals['[字段:'.$field.']']=is_array($fieldVal['values'][$curUrlMd5])?$fieldVal['values'][$curUrlMd5][$loopIndex]:$fieldVal['values'][$curUrlMd5];
                    }
                }
            }
        }
        return $fieldVals;
    }
    private function _replace_insert_fields($paramsStr,$curUrlMd5,$loopIndex){
        $fieldRule='/\[\x{5b57}\x{6bb5}\:(.+?)\]/u';
        return \util\Funcs::txt_replace_params(false, false, $paramsStr, '', $fieldRule, $this->_get_insert_fields($paramsStr, $curUrlMd5, $loopIndex));
    }
}
?>