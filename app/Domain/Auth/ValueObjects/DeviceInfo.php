<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObjects;

use App\Shared\Exceptions\ValidationException;

final readonly class DeviceInfo
{
    public function __construct(
        private string $userAgent,
        private string $ipAddress,
        private ?string $deviceType = null,
        private ?string $browser = null,
        private ?string $os = null,
        private ?string $country = null,
        private ?string $city = null
    ) {
        $this->validate();
    }

    public static function fromRequest(array $requestData): self
    {
        return new self(
            userAgent: $requestData['user_agent'] ?? '',
            ipAddress: $requestData['ip_address'] ?? '',
            deviceType: $requestData['device_type'] ?? null,
            browser: $requestData['browser'] ?? null,
            os: $requestData['os'] ?? null,
            country: $requestData['country'] ?? null,
            city: $requestData['city'] ?? null
        );
    }

    public static function fromHttpRequest(\Illuminate\Http\Request $request): self
    {
        $userAgent = $request->header('User-Agent') ?? '';
        $ipAddress = $request->ip() ?? '';

        // Parse user agent to extract device/browser/OS info
        $deviceInfo = self::parseUserAgent($userAgent);

        return new self(
            userAgent: $userAgent,
            ipAddress: $ipAddress,
            deviceType: $deviceInfo['device_type'],
            browser: $deviceInfo['browser'],
            os: $deviceInfo['os'],
            country: null, // Could be populated with IP geolocation service
            city: null     // Could be populated with IP geolocation service
        );
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    public function ipAddress(): string
    {
        return $this->ipAddress;
    }

    public function deviceType(): ?string
    {
        return $this->deviceType;
    }

    public function browser(): ?string
    {
        return $this->browser;
    }

    public function os(): ?string
    {
        return $this->os;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function isMobile(): bool
    {
        return $this->deviceType === 'mobile';
    }

    public function isDesktop(): bool
    {
        return $this->deviceType === 'desktop';
    }

    public function isTablet(): bool
    {
        return $this->deviceType === 'tablet';
    }

    public function getLocationString(): string
    {
        $parts = array_filter([$this->city, $this->country]);

        return in_array(implode(', ', $parts), ['', '0'], true) ? 'Unknown' : implode(', ', $parts);
    }

    public function toArray(): array
    {
        return [
            'user_agent' => $this->userAgent,
            'ip_address' => $this->ipAddress,
            'device_type' => $this->deviceType,
            'browser' => $this->browser,
            'os' => $this->os,
            'country' => $this->country,
            'city' => $this->city,
            'location' => $this->getLocationString(),
        ];
    }

    private static function parseUserAgent(string $userAgent): array
    {
        // Basic user agent parsing - in production, you might want to use a dedicated library
        $deviceType = 'desktop';
        $browser = null;
        $os = null;

        // Detect device type
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $deviceType = preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        }

        // Detect browser
        if (preg_match('/Chrome\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Chrome '.$matches[1];
        } elseif (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Firefox '.$matches[1];
        } elseif (preg_match('/Safari\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Safari '.$matches[1];
        } elseif (preg_match('/Edge\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Edge '.$matches[1];
        }

        // Detect OS
        if (preg_match('/Windows NT (\d+\.\d+)/', $userAgent, $matches)) {
            $os = 'Windows '.$matches[1];
        } elseif (preg_match('/Mac OS X (\d+[_\d]*)/', $userAgent, $matches)) {
            $os = 'macOS '.str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android (\d+\.\d+)/', $userAgent, $matches)) {
            $os = 'Android '.$matches[1];
        } elseif (preg_match('/iOS (\d+[_\d]*)/', $userAgent, $matches)) {
            $os = 'iOS '.str_replace('_', '.', $matches[1]);
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    private function validate(): void
    {
        if ($this->userAgent === '' || $this->userAgent === '0') {
            throw new ValidationException('User agent cannot be empty');
        }

        if ($this->ipAddress === '' || $this->ipAddress === '0') {
            throw new ValidationException('IP address cannot be empty');
        }

        // Validate IP address format
        if (! filter_var($this->ipAddress, FILTER_VALIDATE_IP)) {
            throw new ValidationException('Invalid IP address format');
        }
    }
}
