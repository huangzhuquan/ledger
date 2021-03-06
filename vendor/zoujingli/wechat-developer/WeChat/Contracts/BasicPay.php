<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | github开源项目：
// +----------------------------------------------------------------------

namespace WeChat\Contracts;

use WeChat\Exceptions\InvalidArgumentException;
use WeChat\Exceptions\InvalidResponseException;

/**
 * 微信支付基础类
 * Class BasicPay
 * @package WeChat\Contracts
 */
class BasicPay
{
    /**
     * 商户配置
     * @var DataArray
     */
    protected $config;

    /**
     * 当前请求数据
     * @var DataArray
     */
    protected $params;


    /**
     * WeChat constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (empty($options['appid'])) {
            throw new InvalidArgumentException("Missing Config -- [appid]");
        }
        if (empty($options['mch_id'])) {
            throw new InvalidArgumentException("Missing Config -- [mch_id]");
        }
        if (empty($options['mch_key'])) {
            throw new InvalidArgumentException("Missing Config -- [mch_key]");
        }
        if (!empty($options['cache_path'])) {
            Tools::$cache_path = $options['cache_path'];
        }
        $this->config = new DataArray($options);
        // 商户基础参数
        $this->params = new DataArray([
            'appid'     => $this->config->get('appid'),
            'mch_id'    => $this->config->get('mch_id'),
            'nonce_str' => Tools::createNoncestr(),
        ]);
        // 商户参数支持
        if ($this->config->get('sub_appid')) {
            $this->params->set('sub_appid', $this->config->get('sub_appid'));
        }
        if ($this->config->get('sub_mch_id')) {
            $this->params->set('sub_mch_id', $this->config->get('sub_mch_id'));
        }
    }

    /**
     * 获取微信支付通知
     * @return array
     * @throws InvalidResponseException
     */
    public function getNotify()
    {
        $data = Tools::xml2arr(file_get_contents('php://input'));
        if (!empty($data['sign'])) {
            if ($this->getPaySign($data) === $data['sign']) {
                return $data;
            }
        }
        throw new InvalidResponseException('Invalid Notify.', '0');
    }

    /**
     * 生成支付签名
     * @param array $data 参与签名的数据
     * @param string $signType 参与签名的类型
     * @param string $buff 参与签名字符串前缀
     * @return string
     */
    public function getPaySign(array $data, $signType = 'MD5', $buff = '')
    {
        unset($data['sign']);
        ksort($data);
        foreach ($data as $k => $v) {
            $buff .= "{$k}={$v}&";
        }
        $buff .= ("key=" . $this->config->get('mch_key'));
        if (strtoupper($signType) === 'MD5') {
            return strtoupper(md5($buff));
        }
        return strtoupper(hash_hmac('SHA256', $buff, $this->config->get('mch_key')));
    }

    /**
     * 转换短链接
     * @param string $longUrl 需要转换的URL，签名用原串，传输需URLencode
     * @return array
     * @throws InvalidResponseException
     */
    public function shortUrl($longUrl)
    {
        $url = 'https://api.mch.weixin.qq.com/tools/shorturl';
        return $this->callPostApi($url, ['long_url' => $longUrl]);
    }

    /**
     * 以Post请求接口
     * @param string $url 请求
     * @param array $data 接口参数
     * @param bool $isCert 是否需要使用双向证书
     * @param string $signType 数据签名类型 MD5|SHA256
     * @param bool $needSignType 是否需要传签名类型参数
     * @return array
     * @throws InvalidResponseException
     */
    protected function callPostApi($url, array $data, $isCert = false, $signType = 'HMAC-SHA256', $needSignType = true)
    {
        $option = [];
        if ($isCert) {
            $option['ssl_cer'] = $this->config->get('ssl_cer');
            $option['ssl_key'] = $this->config->get('ssl_key');
            if (empty($option['ssl_cer']) || !file_exists($option['ssl_cer']))
                throw new InvalidArgumentException("Missing Config -- ssl_cer", '0');
            if (empty($option['ssl_key']) || !file_exists($option['ssl_key']))
                throw new InvalidArgumentException("Missing Config -- ssl_key", '0');
        }
        $params = $this->params->merge($data);
        $needSignType && ($params['sign_type'] = strtoupper($signType));
        $params['sign'] = $this->getPaySign($params, $signType);
        $result = Tools::xml2arr(Tools::post($url, Tools::arr2xml($params), $option));
        if ($result['return_code'] !== 'SUCCESS') {
            throw new InvalidResponseException($result['return_msg'], '0');
        }
        return $result;
    }
}