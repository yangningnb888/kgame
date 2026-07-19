<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Workerman\Protocols;

use Workerman\Connection\TcpConnection;

/**
 * Frame Protocol.
 */
class FrameLua
{
    /**
     * Check the integrity of the package.
     *
     * @param string        $buffer
     * @param TcpConnection $connection
     * @return int
     */
    public static function input($buffer, TcpConnection $connection)
    {
        if (strlen($buffer) < 4) {
            return 0;
        }
        $unpack_data = unpack('Ltotal_length', $buffer);
        return $unpack_data['total_length'] + 4;
    }

    /**
     * 加解密
     * @param $str
     * @return string
     */
    private static function myCrypt($str) {
        $len = strlen($str);
        $ret = '';
        for ($i = 0; $i < $len; $i++) {
            $ret = $ret . (~$str[$i]);
        }
        return $ret;
    }

    /**
     * Decode.
     *
     * @param string $buffer
     * @return string
     */
    public static function decode($buffer)
    {
        $unpack_data = unpack('Ltotal_length', $buffer);
        $str = substr($buffer, 4, $unpack_data['total_length'] - 1);
        return self::myCrypt($str);
    }

    /**
     * Encode.
     *
     * @param string $buffer
     * @return string
     */
    public static function encode($buffer)
    {
        $total_length = strlen($buffer);
        $str = self::myCrypt($buffer);
        return pack('L', $total_length) . $str;
    }
}
