<?php
include './HikvisionISecureCenter.php';

$h      = new HikvisionISecureCenter("https://192.168.1.1", "app_key", "app_secret");
$token  = '';
$params = ['pageNo' => 1, 'pageSize' => 100];
# 取token  用于大批量操作，但是一定要确保token没有过期，自行处理token过期与重新获取
var_dump($h->getToken());
# 取人员列表，第三个参数可不传 使用签名
var_dump($h->doRequest('/api/resource/v1/person/personList', $params, $token));

# 取根组织 没有传token
var_dump($h->doRequest('/api/resource/v1/org/rootOrg', []));
