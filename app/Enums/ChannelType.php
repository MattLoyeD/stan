<?php

namespace App\Enums;

enum ChannelType: string
{
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';
    case Signal = 'signal';
    case Slack = 'slack';
    case Teams = 'teams';
}
