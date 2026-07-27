<?php

namespace App\Services\Terminal;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Net\SSH2;

/**
 * phpseclib3's SSH2::setWindowSize() only affects the initial pty-req sent by
 * openShell() — it has no way to resize an already-open shell channel. This
 * subclass adds that by sending the "window-change" channel request directly
 * (RFC 4254 §6.7), using the protected primitives the parent class exposes.
 */
class ResizableSSH2 extends SSH2
{
    public function resizeTerminal(int $columns, int $rows): void
    {
        if (! isset($this->server_channels[self::CHANNEL_SHELL])) {
            return;
        }

        $packet = Strings::packSSH2(
            'CNsbN4',
            NET_SSH2_MSG_CHANNEL_REQUEST,
            $this->server_channels[self::CHANNEL_SHELL],
            'window-change',
            false, // want_reply
            $columns,
            $rows,
            0,
            0,
        );

        $this->send_binary_packet($packet);
    }
}
