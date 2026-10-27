<?php

namespace App\Services;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;

/**
 * Delivers mail from this process to the recipient's own mail server.
 * No relay, HTTP API, or third-party account is used.
 */
class DirectSmtpTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $ehloDomain = 'localhost',
        private readonly int $timeout = 20,
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'smtp://mx';
    }

    /**
     * @return list<string>
     */
    public function mailServers(string $domain): array
    {
        $domain = strtolower(trim($domain, ". \t"));
        if ($domain === '' || ! function_exists('dns_get_record')) {
            return $domain === '' ? [] : [$domain];
        }

        $records = @dns_get_record($domain, DNS_MX);
        if (! is_array($records) || $records === []) {
            return [$domain];
        }

        usort($records, fn (array $a, array $b): int => ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0));

        $hosts = [];
        foreach ($records as $record) {
            $target = strtolower(rtrim((string) ($record['target'] ?? ''), '.'));
            if ($target !== '' && $target !== '.') {
                $hosts[] = $target;
            }
        }

        return $hosts === [] ? [$domain] : array_values(array_unique($hosts));
    }

    public function dotStuff(string $message): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $message);
        $lines = explode("\n", $normalized);
        $stuffed = array_map(
            fn (string $line): string => str_starts_with($line, '.') ? '.'.$line : $line,
            $lines,
        );

        return implode("\r\n", $stuffed);
    }

    protected function doSend(SentMessage $message): void
    {
        $sender = $message->getEnvelope()->getSender()->getAddress();
        $raw = $this->dotStuff($message->getOriginalMessage()->toString());
        $failures = [];

        foreach ($message->getEnvelope()->getRecipients() as $recipient) {
            try {
                $this->deliver($sender, $recipient->getAddress(), $raw);
            } catch (\Throwable $e) {
                $failures[] = $recipient->getAddress().': '.$e->getMessage();
            }
        }

        if ($failures !== []) {
            throw new TransportException('Gagal mengirim email langsung: '.implode(' | ', $failures));
        }
    }

    private function deliver(string $from, string $to, string $data): void
    {
        $domain = strtolower((string) substr(strrchr($to, '@') ?: '', 1));
        if ($domain === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new TransportException('Alamat penerima tidak valid.');
        }

        $last = null;
        foreach ($this->mailServers($domain) as $host) {
            try {
                $this->deliverToHost($host, $from, $to, $data);

                return;
            } catch (\Throwable $e) {
                $last = $e;
            }
        }

        throw new TransportException($last?->getMessage() ?? 'Server surat tujuan tidak dapat dihubungi.', 0, $last);
    }

    private function deliverToHost(string $host, string $from, string $to, string $data): void
    {
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            'tcp://'.$host.':25',
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
        );

        if ($socket === false) {
            throw new TransportException('Tidak dapat terhubung ke '.$host.':25 ('.$errstr.').');
        }

        stream_set_timeout($socket, $this->timeout);

        try {
            $this->expect($socket, [220]);
            $greeting = $this->command($socket, 'EHLO '.$this->ehloName(), [250]);

            if (stripos($greeting, 'STARTTLS') !== false) {
                $this->command($socket, 'STARTTLS', [220]);
                $encrypted = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($encrypted !== true) {
                    throw new TransportException('STARTTLS gagal pada '.$host.'.');
                }
                $this->command($socket, 'EHLO '.$this->ehloName(), [250]);
            }

            $this->command($socket, 'MAIL FROM:<'.$this->path($from).'>', [250]);
            $this->command($socket, 'RCPT TO:<'.$this->path($to).'>', [250, 251]);
            $this->command($socket, 'DATA', [354]);
            fwrite($socket, $data."\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function ehloName(): string
    {
        $name = strtolower(trim($this->ehloDomain));
        if ($name === '' || $name === 'localhost') {
            return 'kelas.brahmanapriambudi.my.id';
        }

        return $name;
    }

    private function path(string $address): string
    {
        $parsed = Address::create($address)->getAddress();

        return str_replace(["\r", "\n", '<', '>'], '', $parsed);
    }

    /**
     * @param  resource  $socket
     * @param  list<int>  $ok
     */
    private function command($socket, string $line, array $ok): string
    {
        fwrite($socket, $line."\r\n");

        return $this->expect($socket, $ok);
    }

    /**
     * @param  resource  $socket
     * @param  list<int>  $ok
     */
    private function expect($socket, array $ok): string
    {
        $response = '';
        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new TransportException('Server surat menutup koneksi.');
        }

        $code = (int) substr($response, 0, 3);
        if (! in_array($code, $ok, true)) {
            throw new TransportException(trim(preg_replace('/\s+/', ' ', $response) ?? $response));
        }

        return $response;
    }
}
