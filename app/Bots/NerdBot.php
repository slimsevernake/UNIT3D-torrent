<?php
declare(strict_types=1);
/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     HDVinnie <hdinnovations@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Bots;

class NerdBot
{
    private $bot;
    private \App\Repositories\ChatRepository $chat;
    private $target;
    private $type;
    private $message;
    private $targeted;
    private $log;
    private \Carbon\Carbon $expiresAt;
    private \Carbon\Carbon $current;

    /**
     * NerdBot Constructor.
     *
     * @param \App\Repositories\ChatRepository $chatRepository
     */
    public function __construct(\App\Repositories\ChatRepository $chatRepository)
    {
        $bot = \App\Models\Bot::where('id', '=', '2')->firstOrFail();
        $this->chat = $chatRepository;
        $this->bot = $bot;
        $this->expiresAt = \Carbon\Carbon::now()->addMinutes(60);
        $this->current = \Carbon\Carbon::now();
    }

    /**
     * Replace Vars.
     *
     * @param $output
     *
     * @return mixed
     */
    public function replaceVars($output)
    {
        $output = \str_replace(['{me}', '{command}'], [$this->bot->name, $this->bot->command], $output);
        if (\strpos($output, '{bots}') !== false) {
            $botHelp = '';
            $bots = \App\Models\Bot::where('active', '=', 1)->where('id', '!=', $this->bot->id)->orderBy('position', 'asc')->get();
            foreach ($bots as $bot) {
                $botHelp .= '( ! | / | @)'.$bot->command.' help triggers help file for '.$bot->name."\n";
            }
            $output = \str_replace('{bots}', $botHelp, $output);
        }

        return $output;
    }

    /**
     * Get Banker.
     *
     * @return string
     * @throws \Exception
     */
    public function getBanker()
    {
        $banker = \cache()->get('nerdbot-banker');
        if (! $banker || $banker == null) {
            $banker = \App\Models\User::latest('seedbonus')->first();
            \cache()->put('nerdbot-banker', $banker, $this->expiresAt);
        }

        return \sprintf('Currently [url=/users/%s]%s[/url] Is The Top BON Holder On ', $banker->username, $banker->username).\config('other.title').'!';
    }

    /**
     * Get Snatched.
     *
     * @return string
     * @throws \Exception
     */
    public function getSnatched()
    {
        $snatched = \cache()->get('nerdbot-snatched');
        if (! $snatched || $snatched == null) {
            $snatched = \App\Models\Torrent::latest('times_completed')->first();
            \cache()->put('nerdbot-snatched', $snatched, $this->expiresAt);
        }

        return \sprintf('Currently [url=/torrents/%s]%s[/url] Is The Most Snatched Torrent On ', $snatched->id, $snatched->name).\config('other.title').'!';
    }

    /**
     * Get Leeched.
     *
     * @return string
     * @throws \Exception
     */
    public function getLeeched()
    {
        $leeched = \cache()->get('nerdbot-leeched');
        if (! $leeched || $leeched == null) {
            $leeched = \App\Models\Torrent::latest('leechers')->first();
            \cache()->put('nerdbot-leeched', $leeched, $this->expiresAt);
        }

        return \sprintf('Currently [url=/torrents/%s]%s[/url] Is The Most Leeched Torrent On ', $leeched->id, $leeched->name).\config('other.title').'!';
    }

    /**
     * Get Seeded.
     *
     * @return string
     * @throws \Exception
     */
    public function getSeeded()
    {
        $seeded = \cache()->get('nerdbot-seeded');
        if (! $seeded || $seeded == null) {
            $seeded = \App\Models\Torrent::latest('seeders')->first();
            \cache()->put('nerdbot-seeded', $seeded, $this->expiresAt);
        }

        return \sprintf('Currently [url=/torrents/%s]%s[/url] Is The Most Seeded Torrent On ', $seeded->id, $seeded->name).\config('other.title').'!';
    }

    /**
     * Get FL.
     *
     * @return string
     * @throws \Exception
     */
    public function getFreeleech()
    {
        $fl = \cache()->get('nerdbot-fl');
        if (! $fl || $fl == null) {
            $fl = \App\Models\Torrent::where('free', '=', 1)->count();
            \cache()->put('nerdbot-fl', $fl, $this->expiresAt);
        }

        return \sprintf('There Are Currently %s Freeleech Torrents On ', $fl).\config('other.title').'!';
    }

    /**
     * Get DU.
     *
     * @return string
     * @throws \Exception
     */
    public function getDoubleUpload()
    {
        $du = \cache()->get('nerdbot-doubleup');
        if (! $du || $du == null) {
            $du = \App\Models\Torrent::where('doubleup', '=', 1)->count();
            \cache()->put('nerdbot-doubleup', $du, $this->expiresAt);
        }

        return \sprintf('There Are Currently %s Double Upload Torrents On ', $du).\config('other.title').'!';
    }

    /**
     * Get Peers.
     *
     * @return string
     * @throws \Exception
     */
    public function getPeers()
    {
        $peers = \cache()->get('nerdbot-peers');
        if (! $peers || $peers == null) {
            $peers = \App\Models\Peer::count();
            \cache()->put('nerdbot-peers', $peers, $this->expiresAt);
        }

        return \sprintf('Currently There Are %s Peers On ', $peers).\config('other.title').'!';
    }

    /**
     * Get Bans.
     *
     * @return string
     * @throws \Exception
     */
    public function getBans()
    {
        $bans = \cache()->get('nerdbot-bans');
        if (! $bans || $bans == null) {
            $bans = \App\Models\Ban::whereNull('unban_reason')->whereNull('removed_at')->where('created_at', '>', $this->current->subDay())->count();
            \cache()->put('nerdbot-bans', $bans, $this->expiresAt);
        }

        return \sprintf('In The Last 24 Hours %s Users Have Been Banned From ', $bans).\config('other.title').'!';
    }

    /**
     * Get Warnings.
     *
     * @return string
     * @throws \Exception
     */
    public function getWarnings()
    {
        $warnings = \cache()->get('nerdbot-warnings');
        if (! $warnings || $warnings == null) {
            $warnings = \App\Models\Warning::where('created_at', '>', $this->current->subDay())->count();
            \cache()->put('nerdbot-warnings', $warnings, $this->expiresAt);
        }

        return \sprintf('In The Last 24 Hours %s Hit and Run Warnings Have Been Issued On ', $warnings).\config('other.title').'!';
    }

    /**
     * Get Uploads.
     *
     * @return string
     * @throws \Exception
     */
    public function getUploads()
    {
        $uploads = \cache()->get('nerdbot-uploads');
        if (! $uploads || $uploads == null) {
            $uploads = \App\Models\Torrent::where('created_at', '>', $this->current->subDay())->count();
            \cache()->put('nerdbot-uploads', $uploads, $this->expiresAt);
        }

        return \sprintf('In The Last 24 Hours %s Torrents Have Been Uploaded To ', $uploads).\config('other.title').'!';
    }

    /**
     * Get Logins.
     *
     * @return string
     * @throws \Exception
     */
    public function getLogins()
    {
        $logins = \cache()->get('nerdbot-logins');
        if (! $logins || $logins == null) {
            $logins = \App\Models\User::whereNotNull('last_login')->where('last_login', '>', $this->current->subDay())->count();
            \cache()->put('nerdbot-logins', $logins, $this->expiresAt);
        }

        return \sprintf('In The Last 24 Hours %s Unique Users Have Logged Into ', $logins).\config('other.title').'!';
    }

    /**
     * Get Registrations.
     *
     * @return string
     * @throws \Exception
     */
    public function getRegistrations()
    {
        $registrations = \cache()->get('nerdbot-users');
        if (! $registrations || $registrations == null) {
            $registrations = \App\Models\User::where('created_at', '>', $this->current->subDay())->count();
            \cache()->put('nerdbot-users', $registrations, $this->expiresAt);
        }

        return \sprintf('In The Last 24 Hours %s Users Have Registered To ', $registrations).\config('other.title').'!';
    }

    /**
     * Get Bot Donations.
     *
     * @return string
     * @throws \Exception
     */
    public function getDonations()
    {
        $donations = \cache()->get('nerdbot-donations');
        if (! $donations || $donations == null) {
            $donations = \App\Models\BotTransaction::with('user', 'bot')->where('to_bot', '=', 1)->latest()->limit(10)->get();
            \cache()->put('nerdbot-donations', $donations, $this->expiresAt);
        }
        $donationDump = '';
        $i = 1;
        foreach ($donations as $donation) {
            $donationDump .= '#'.$i.'. '.$donation->user->username.' sent '.$donation->bot->name.' '.$donation->cost.' '.$donation->forHumans().".\n";
            $i++;
        }

        return "The Most Recent Donations To All Bots Are As Follows:\n\n".\trim($donationDump);
    }

    /**
     * Get Help.
     */
    public function getHelp()
    {
        return $this->replaceVars($this->bot->help);
    }

    /**
     * Get King.
     */
    public function getKing()
    {
        return \config('other.title').' Is King!';
    }

    /**
     * Send Bot Donation.
     *
     * @param int    $amount
     * @param string $note
     *
     * @throws \Exception
     *
     * @return string
     */
    public function putDonate($amount = 0, $note = '')
    {
        $output = \implode((array)' ', $note);
        $v = \validator(['bot_id' => $this->bot->id, 'amount' => $amount, 'note' => $output], ['bot_id' => 'required|exists:bots,id|max:999', 'amount' => \sprintf('required|numeric|min:1|max:%s', $this->target->seedbonus), 'note' => 'required|string']);
        if ($v->passes()) {
            $value = $amount;
            $this->bot->seedbonus += $value;
            $this->bot->save();
            $this->target->seedbonus -= $value;
            $this->target->save();
            $botTransaction = new \App\Models\BotTransaction();
            $botTransaction->type = 'bon';
            $botTransaction->cost = $value;
            $botTransaction->user_id = $this->target->id;
            $botTransaction->bot_id = $this->bot->id;
            $botTransaction->to_bot = 1;
            $botTransaction->comment = $output;
            $botTransaction->save();
            $donations = \App\Models\BotTransaction::with('user', 'bot')->where('bot_id', '=', $this->bot->id)->where('to_bot', '=', 1)->latest()->limit(10)->get();
            \cache()->put('casinobot-donations', $donations, $this->expiresAt);

            return 'Your donation to '.$this->bot->name.' for '.$amount.' BON has been sent!';
        }

        return 'Your donation to '.$output.' could not be sent.';
    }

    /**
     * Process Message.
     *
     * @param                  $type
     * @param \App\Models\User $user
     * @param string           $message
     * @param int              $targeted
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function process($type, \App\Models\User $user, $message = '', $targeted = 0)
    {
        $this->target = $user;
        if ($type === 'message') {
            $x = 0;
            $y = 1;
            $z = 2;
        } else {
            $x = 1;
            $y = 2;
            $z = 3;
        }
        if ($message === '') {
            $log = '';
        } else {
            $log = 'All '.$this->bot->name.' commands must be a private message or begin with /'.$this->bot->command.' or !'.$this->bot->command.'. Need help? Type /'.$this->bot->command.' help and you shall be helped.';
        }
        $command = @\explode(' ', $message);
        $wildcard = null;
        $params = null;
        if (\array_key_exists($y, $command)) {
            $params = $command[$y];
        }
        if ($params != null) {
            $clone = $command;
            \array_shift($clone);
            \array_shift($clone);
            \array_shift($clone);
            $wildcard = $clone;
        }
        if (\array_key_exists($x, $command)) {
            if ($command[$x] === 'banker') {
                $log = $this->getBanker();
            }
            if ($command[$x] === 'bans') {
                $log = $this->getBans();
            }
            if ($command[$x] === 'donations') {
                $log = $this->getDonations();
            }
            if ($command[$x] === 'donate') {
                $log = $this->putDonate($params, $wildcard);
            }
            if ($command[$x] === 'doubleupload') {
                $log = $this->getDoubleUpload();
            }
            if ($command[$x] === 'freeleech') {
                $log = $this->getFreeleech();
            }
            if ($command[$x] === 'help') {
                $log = $this->getHelp();
            }
            if ($command[$x] === 'king') {
                $log = $this->getKing();
            }
            if ($command[$x] === 'logins') {
                $log = $this->getLogins();
            }
            if ($command[$x] === 'peers') {
                $log = $this->getPeers();
            }
            if ($command[$x] === 'registrations') {
                $log = $this->getRegistrations();
            }
            if ($command[$x] === 'uploads') {
                $log = $this->getUploads();
            }
            if ($command[$x] === 'warnings') {
                $log = $this->getWarnings();
            }
            if ($command[$x] === 'seeded') {
                $log = $this->getSeeded();
            }
            if ($command[$x] === 'leeched') {
                $log = $this->getLeeched();
            }
            if ($command[$x] === 'snatched') {
                $log = $this->getSnatched();
            }
        }
        $this->targeted = $targeted;
        $this->type = $type;
        $this->message = $message;
        $this->log = $log;

        return $this->pm();
    }

    /**
     * Output Message.
     */
    public function pm()
    {
        $type = $this->type;
        $target = $this->target;
        $txt = $this->log;
        $message = $this->message;
        $targeted = $this->targeted;
        if ($type === 'message' || $type === 'private') {
            $receiverDirty = 0;
            $receiverEchoes = \cache()->get('user-echoes'.$target->id);
            if (! $receiverEchoes || ! \is_array($receiverEchoes) || (\is_countable($receiverEchoes) ? \is_countable($receiverEchoes) ? \count($receiverEchoes) : 0 : 0) < 1) {
                $receiverEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$target->id])->get();
            }
            $receiverListening = false;
            foreach ($receiverEchoes as $se => $receiverEcho) {
                if ($receiverEcho['bot_id'] == $this->bot->id) {
                    $receiverListening = true;
                }
            }
            if (! $receiverListening) {
                $receiverPort = new \App\Models\UserEcho();
                $receiverPort->user_id = $target->id;
                $receiverPort->bot_id = $this->bot->id;
                $receiverPort->save();
                $receiverEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$target->id])->get();
                $receiverDirty = 1;
            }
            if ($receiverDirty == 1) {
                $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
                \cache()->put('user-echoes'.$target->id, $receiverEchoes, $expiresAt);
                \event(new \App\Events\Chatter('echo', $target->id, \App\Http\Resources\UserEchoResource::collection($receiverEchoes)));
            }
            $receiverDirty = 0;
            $receiverAudibles = \cache()->get('user-audibles'.$target->id);
            if (! $receiverAudibles || ! \is_array($receiverAudibles) || (\is_countable($receiverAudibles) ? \is_countable($receiverAudibles) ? \count($receiverAudibles) : 0 : 0) < 1) {
                $receiverAudibles = \App\Models\UserAudible::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$target->id])->get();
            }
            $receiverListening = false;
            foreach ($receiverAudibles as $se => $receiverEcho) {
                if ($receiverEcho['bot_id'] == $this->bot->id) {
                    $receiverListening = true;
                }
            }
            if (! $receiverListening) {
                $receiverPort = new \App\Models\UserAudible();
                $receiverPort->user_id = $target->id;
                $receiverPort->bot_id = $this->bot->id;
                $receiverPort->save();
                $receiverAudibles = \App\Models\UserAudible::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$target->id])->get();
                $receiverDirty = 1;
            }
            if ($receiverDirty == 1) {
                $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
                \cache()->put('user-audibles'.$target->id, $receiverAudibles, $expiresAt);
                \event(new \App\Events\Chatter('audible', $target->id, \App\Http\Resources\UserAudibleResource::collection($receiverAudibles)));
            }
            if ($txt != '') {
                $roomId = 0;
                $message = $this->chat->privateMessage($target->id, $message, 1, $this->bot->id);
                $message = $this->chat->privateMessage(1, $txt, $target->id, $this->bot->id);
            }

            return \response('success');
        }
        if ($type === 'echo') {
            if ($txt != '') {
                $roomId = 0;
                $message = $this->chat->botMessage($this->bot->id, $txt, $target->id);
            }

            return \response('success');
        }
        if ($type === 'public') {
            if ($txt != '') {
                $dumproom = $this->chat->message($target->id, $target->chatroom->id, $message, null, null);
                $dumproom = $this->chat->message(1, $target->chatroom->id, $txt, null, $this->bot->id);
            }

            return \response('success');
        }

        return true;
    }
}
