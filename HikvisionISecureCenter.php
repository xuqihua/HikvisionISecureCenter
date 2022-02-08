<?php
/**
 * Hikvision iSecure Center 接口
 * https://open.hikvision.com/docs/docId?productId=5c67f1e2f05948198c909700
 *
 */

class HikvisionISecureCenter
{
    public    $base_url   = "/";
    public    $base_path  = "/artemis";
    protected $app_key    = "";
    protected $app_secret = "";

    public $content_type = "application/json";
    public $accept       = "*/*";
    public $token        = ""; // token 可自行缓存

    public function __construct($base_url, $app_key = '', $app_secret = '')
    {
        $this->base_url   = $base_url;
        $this->app_key    = $app_key;
        $this->app_secret = $app_secret;
    }

    /**
     * 获取token
     */
    function getToken()
    {
        return $this->doRequest('/api/v1/oauth/token', []);
    }

    public function getTime()
    {
        list($msecond, $second) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($msecond) + floatval($second)) * 1000);
    }

    /**
     * 以appSecret为密钥，使用HmacSHA256算法对签名字符串生成消息摘要，对消息摘要使用BASE64算法生成签名（签名过程中的编码方式全为UTF-8）
     */
    public function getSign($url, $time)
    {
        $next     = "\n";
        $sign_str = "POST".$next.$this->accept.$next.$this->content_type.$next;
        $sign_str .= "x-ca-key:".$this->app_key.$next;
        $sign_str .= "x-ca-timestamp:".$time.$next;
        $sign_str .= $url;
        $priKey   = $this->app_secret;
        $sign     = hash_hmac('sha256', $sign_str, $priKey, true); //生成消息摘要
        $result   = base64_encode($sign);

        return $result;
    }

    public function doRequest($path, $params = [], $token = '')
    {
        $fullpath = $this->base_path.$path;
        if ($token) {
            $this->token = $token;
        }
        if ($this->token) {
            $options = [
                CURLOPT_HTTPHEADER => [
                    'Accept:'.$this->accept,
                    'Content-Type:'.$this->content_type,
                    'access_token:'.$this->token,
                ],
            ];
        } else {
            $time    = $this->getTime();
            $sign    = $this->getSign($fullpath, $time);
            $options = [
                CURLOPT_HTTPHEADER => array(
                    "Accept:".$this->accept,
                    "Content-Type:".$this->content_type,
                    "x-Ca-Key:".$this->app_key,
                    "X-Ca-Signature:".$sign,
                    "X-Ca-Timestamp:".$time,
                    "X-Ca-Signature-Headers:"."x-ca-key,x-ca-timestamp",
                )
            ];
        }
        $result = $this->curlPost($this->base_url.$fullpath, json_encode($params), $options);

        return json_decode($result, true);
    }

    public function curlPost($url = '', $postData = '', $options = [])
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
