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

namespace App\Http\Controllers;

use App\Helpers\ByteUnits;
use App\Models\Torrent;
use App\Models\User;
use App\Repositories\ChatRepository;

/**
 * @see \Tests\Feature\Http\Controllers\BonusControllerTest
 */
class BonusController extends \App\Http\Controllers\Controller
{
    /**
     * @var ChatRepository
     */
    private ChatRepository $chatRepository;
    /**
     * The library used for parsing byte units.
     *
     * @var ByteUnits
     */
    protected $byteUnits;

    /**
     * BonusController Constructor.
     *
     * @param \App\Interfaces\ByteUnitsInterface $byteUnits
     * @param \App\Repositories\ChatRepository   $chatRepository
     */
    public function __construct(\App\Interfaces\ByteUnitsInterface $byteUnits, \App\Repositories\ChatRepository $chatRepository)
    {
        $this->byteUnits = $byteUnits;
        $this->chatRepository = $chatRepository;
    }

    /**
     * Show Bonus Gifts System.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function gifts(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $userbon = $user->getSeedbonus();
        $gifttransactions = \App\Models\BonTransactions::with(['senderObj', 'receiverObj'])->where(function ($query) use ($user) {
            $query->where('sender', '=', $user->id)->orwhere('receiver', '=', $user->id);
        })->where('name', '=', 'gift')->orderBy('date_actioned', 'DESC')->paginate(25);
        $giftsSent = \App\Models\BonTransactions::where('sender', '=', $user->id)->where('name', '=', 'gift')->sum('cost');
        $giftsReceived = \App\Models\BonTransactions::where('receiver', '=', $user->id)->where('name', '=', 'gift')->sum('cost');

        return \view('bonus.gifts', ['user' => $user, 'gifttransactions' => $gifttransactions, 'userbon' => $userbon, 'gifts_sent' => $giftsSent, 'gifts_received' => $giftsReceived]);
    }

    /**
     * Show Bonus Tips System.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tips(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $userbon = $user->getSeedbonus();
        $bontransactions = \App\Models\BonTransactions::with(['senderObj', 'receiverObj'])->where(function ($query) use ($user) {
            $query->where('sender', '=', $user->id)->orwhere('receiver', '=', $user->id);
        })->where('name', '=', 'tip')->orderBy('date_actioned', 'DESC')->paginate(25);
        $tipsSent = \App\Models\BonTransactions::where('sender', '=', $user->id)->where('name', '=', 'tip')->sum('cost');
        $tipsReceived = \App\Models\BonTransactions::where('receiver', '=', $user->id)->where('name', '=', 'tip')->sum('cost');

        return \view('bonus.tips', ['user' => $user, 'bontransactions' => $bontransactions, 'userbon' => $userbon, 'tips_sent' => $tipsSent, 'tips_received' => $tipsReceived]);
    }

    /**
     * Show Bonus Store System.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $userbon = $user->getSeedbonus();
        $activefl = \App\Models\PersonalFreeleech::where('user_id', '=', $user->id)->first();
        $BonExchange = new \App\Models\BonExchange();
        $bontransactions = \App\Models\BonTransactions::with('exchange')->where('sender', '=', $user->id)->where('itemID', '>', 0)->orderBy('date_actioned', 'DESC')->limit(25)->get();
        $uploadOptions = $BonExchange->getUploadOptions();
        $downloadOptions = $BonExchange->getDownloadOptions();
        $personalFreeleech = $BonExchange->getPersonalFreeleechOption();
        $invite = $BonExchange->getInviteOption();

        return \view('bonus.store', ['userbon' => $userbon, 'activefl' => $activefl, 'bontransactions' => $bontransactions, 'uploadOptions' => $uploadOptions, 'downloadOptions' => $downloadOptions, 'personalFreeleech' => $personalFreeleech, 'invite' => $invite]);
    }

    /**
     * Show Bonus Gift System.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function gift(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $userbon = $user->getSeedbonus();

        return \view('bonus.gift', ['userbon' => $userbon]);
    }

    /**
     * Show Bonus Earnings System.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $username
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function bonus(\Illuminate\Http\Request $request, $username = '')
    {
        $user = $request->user();
        $userbon = $user->getSeedbonus();
        //Dying Torrent
        $dying = $this->getDyingCount($request);
        //Legendary Torrents
        $legendary = $this->getLegendaryCount($request);
        //Old Torrents
        $old = $this->getOldCount($request);
        //Large Torrents
        $huge = $this->getHugeCount($request);
        //Large Torrents
        $large = $this->getLargeCount($request);
        //Everyday Torrents
        $regular = $this->getRegularCount($request);
        //Participant Seeder
        $participant = $this->getParticipaintSeedCount($request);
        //TeamPlayer Seeder
        $teamplayer = $this->getTeamPlayerSeedCount($request);
        //Committed Seeder
        $committed = $this->getCommitedSeedCount($request);
        //MVP Seeder
        $mvp = $this->getMVPSeedCount($request);
        //Legend Seeder
        $legend = $this->getLegendarySeedCount($request);
        //Total points per hour
        $total = $dying * 2 + $legendary * 1.5 + $old * 1 + $huge * 0.75 + $large * 0.5 + $regular * 0.25 + $participant * 0.25 + $teamplayer * 0.5 + $committed * 0.75 + $mvp * 1 + $legend * 2;
        $daily = $total * 24;
        $weekly = $total * 24 * 7;
        $monthly = $total * 24 * 30;
        $yearly = $total * 24 * 365;
        $minute = $total / 60;
        $second = $minute / 60;

        return \view('bonus.index', ['userbon' => $userbon, 'dying' => $dying, 'legendary' => $legendary, 'old' => $old, 'huge' => $huge, 'large' => $large, 'regular' => $regular, 'participant' => $participant, 'teamplayer' => $teamplayer, 'committed' => $committed, 'mvp' => $mvp, 'legend' => $legend, 'total' => $total, 'daily' => $daily, 'weekly' => $weekly, 'monthly' => $monthly, 'yearly' => $yearly, 'username' => $username, 'minute' => $minute, 'second' => $second]);
    }

    /**
     * Exchange Points For A Item.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\BonExchange  $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exchange(\Illuminate\Http\Request $request, \App\Models\BonExchange $id)
    {
        $user = $request->user();
        $userbon = $user->seedbonus;
        $BonExchange = \resolve(\App\Models\BonExchange::class);
        $itemCost = $BonExchange->getItemCost($id);
        if ($userbon >= $itemCost) {
            $flag = $this->doItemExchange($user->id, $id);
            if ($flag === '') {
                return \redirect()->route('bonus_store')->withErrors('Bonus Exchange Failed!');
            }
            $user->seedbonus -= $itemCost;
            $user->save();
        } else {
            return \redirect()->route('bonus_store')->withErrors('Bonus Exchange Failed!');
        }

        return \redirect()->route('bonus_store')->withSuccess('Bonus Exchange Successful');
    }

    /**
     * Do Item Exchange.
     *
     * @param \App\Models\User        $userID
     * @param \App\Models\BonExchange $itemID
     *
     * @return string
     */
    public function doItemExchange(User $userID, \App\Models\BonExchange $itemID)
    {
        $current = \Carbon\Carbon::now();
        $item = \App\Models\BonExchange::where('id', '=', $itemID)->get()->toArray()[0];
        $userAcc = \App\Models\User::findOrFail($userID);
        $activefl = \App\Models\PersonalFreeleech::where('user_id', '=', $userAcc->id)->first();
        $bonTransactions = \resolve(\App\Models\BonTransactions::class);
        if ($item['upload'] == true) {
            $userAcc->uploaded += $item['value'];
            $userAcc->save();
        } elseif ($item['download'] == true) {
            if ($userAcc->downloaded >= $item['value']) {
                $userAcc->downloaded -= $item['value'];
                $userAcc->save();
            } else {
                return false;
            }
        } elseif ($item['personal_freeleech'] == true) {
            if (! $activefl) {
                $personalFreeleech = new \App\Models\PersonalFreeleech();
                $personalFreeleech->user_id = $userAcc->id;
                $personalFreeleech->save();
                // Send Private Message
                $privateMessage = new \App\Models\PrivateMessage();
                $privateMessage->sender_id = 1;
                $privateMessage->receiver_id = $userAcc->id;
                $privateMessage->subject = 'Personal 24 Hour Freeleech Activated';
                $privateMessage->message = \sprintf('Your [b]Personal 24 Hour Freeleech[/b] session has started! It will expire on %s [b]', $current->addDays(1)->toDayDateTimeString()).\config('app.timezone').'[/b]! 
                [color=red][b]THIS IS AN AUTOMATED SYSTEM MESSAGE, PLEASE DO NOT REPLY![/b][/color]';
                $privateMessage->save();
            } else {
                return false;
            }
        } elseif ($item['invite'] == true) {
            $userAcc->invites += $item['value'];
            if ($userAcc->invites) {
                $userAcc->save();
            } else {
                return false;
            }
        }
        $bonTransactions->itemID = $item['id'];
        $bonTransactions->name = $item['description'];
        $bonTransactions->cost = $item['value'];
        $bonTransactions->sender = $userID;
        $bonTransactions->comment = $item['description'];
        $bonTransactions->torrent_id = null;
        $bonTransactions->save();

        return true;
    }

    /**
     * Gift Points To A User.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendGift(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $v = \validator($request->all(), ['to_username' => 'required|exists:users,username|max:180', 'bonus_points' => \sprintf('required|numeric|min:1|max:%s', $user->seedbonus), 'bonus_message' => 'required|string']);
        $dest = 'default';
        if ($request->has('dest') && $request->input('dest') === 'profile') {
            $dest = 'profile';
        }
        if ($v->passes()) {
            $recipient = \App\Models\User::where('username', '=', $request->input('to_username'))->first();
            if (! $recipient || $recipient->id == $user->id) {
                return \redirect()->route('bonus_store')->withErrors('Unable to find specified user');
            }
            $value = $request->input('bonus_points');
            $recipient->seedbonus += $value;
            $recipient->save();
            $user->seedbonus -= $value;
            $user->save();
            $bonTransactions = new \App\Models\BonTransactions();
            $bonTransactions->itemID = 0;
            $bonTransactions->name = 'gift';
            $bonTransactions->cost = $value;
            $bonTransactions->sender = $user->id;
            $bonTransactions->receiver = $recipient->id;
            $bonTransactions->comment = $request->input('bonus_message');
            $bonTransactions->torrent_id = null;
            $bonTransactions->save();
            if ($user->id != $recipient->id && $recipient->acceptsNotification($request->user(), $recipient, 'bon', 'show_bon_gift')) {
                $recipient->notify(new \App\Notifications\NewBon('gift', $user->username, $bonTransactions));
            }
            $profileUrl = \href_profile($user);
            $recipientUrl = \href_profile($recipient);
            $this->chatRepository->systemMessage(\sprintf('[url=%s]%s[/url] has gifted %s BON to [url=%s]%s[/url]', $profileUrl, $user->username, $value, $recipientUrl, $recipient->username));
            if ($dest === 'profile') {
                return \redirect()->route('users.show', ['username' => $recipient->username])->withSuccess('Gift Sent');
            }

            return \redirect()->route('bonus_gift')->withSuccess('Gift Sent');
        }
        $v = \validator($request->all(), ['to_username' => 'required|exists:users,username|max:180']);
        if ($v->passes()) {
            $recipient = \App\Models\User::where('username', 'LIKE', $request->input('to_username'))->first();
            if (! $recipient || $recipient->id == $user->id) {
                return \redirect()->route('bonus_store')->withErrors('Unable to find specified user');
            }
            if ($dest === 'profile') {
                return \redirect()->route('users.show', ['username' => $recipient->username])->withErrors('You Must Enter An Amount And Message!');
            }

            return \redirect()->route('bonus_gift')->withErrors('You Must Enter An Amount And Message!');
        }

        return \redirect()->route('bonus_store')->withErrors('Unable to find specified user');
    }

    /**
     * Tip Points To A Uploader.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Torrent      $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function tipUploader(\Illuminate\Http\Request $request, Torrent $id)
    {
        $user = $request->user();
        $torrent = \App\Models\Torrent::withAnyStatus()->findOrFail($id);
        $uploader = \App\Models\User::where('id', '=', $torrent->user_id)->first();
        $tipAmount = $request->input('tip');
        if ($tipAmount > $user->seedbonus) {
            return \redirect()->route('torrent', ['id' => $torrent->id])->withErrors('Your To Broke To Tip The Uploader!');
        }
        if ($user->id == $torrent->user_id) {
            return \redirect()->route('torrent', ['id' => $torrent->id])->withErrors('You Cannot Tip Yourself!');
        }
        if ($tipAmount <= 0) {
            return \redirect()->route('torrent', ['id' => $torrent->id])->withErrors('You Cannot Tip A Negative Amount!');
        }
        $uploader->seedbonus += $tipAmount;
        $uploader->save();
        $user->seedbonus -= $tipAmount;
        $user->save();
        $bonTransactions = new \App\Models\BonTransactions();
        $bonTransactions->itemID = 0;
        $bonTransactions->name = 'tip';
        $bonTransactions->cost = $tipAmount;
        $bonTransactions->sender = $user->id;
        $bonTransactions->receiver = $uploader->id;
        $bonTransactions->comment = 'tip';
        $bonTransactions->torrent_id = $torrent->id;
        $bonTransactions->save();
        if ($uploader->acceptsNotification($request->user(), $uploader, 'torrent', 'show_torrent_tip')) {
            $uploader->notify(new \App\Notifications\NewUploadTip('torrent', $user->username, $tipAmount, $torrent));
        }

        return \redirect()->route('torrent', ['id' => $torrent->id])->withSuccess('Your Tip Was Successfully Applied!');
    }

    /**
     * Tip Points To A Poster.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function tipPoster(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        if ($request->has('post') && $request->input('post') > 0) {
            $post = \App\Models\Post::with('topic')->findOrFail($request->input('post'));
            $poster = \App\Models\User::where('id', '=', $post->user_id)->firstOrFail();
        } else {
            \abort(404);
        }
        $tipAmount = $request->input('tip');
        if ($tipAmount > $user->seedbonus) {
            return \redirect()->route('forum_topic', ['id' => $post->topic->id])->withErrors('You Are To Broke To Tip The Poster!');
        }
        if ($user->id == $poster->id) {
            return \redirect()->route('forum_topic', ['id' => $post->topic->id])->withErrors('You Cannot Tip Yourself!');
        }
        if ($tipAmount <= 0) {
            return \redirect()->route('forum_topic', ['id' => $post->topic->id])->withErrors('You Cannot Tip A Negative Amount!');
        }
        $poster->seedbonus += $tipAmount;
        $poster->save();
        $user->seedbonus -= $tipAmount;
        $user->save();
        $bonTransactions = new \App\Models\BonTransactions();
        $bonTransactions->itemID = 0;
        $bonTransactions->name = 'tip';
        $bonTransactions->cost = $tipAmount;
        $bonTransactions->sender = $user->id;
        $bonTransactions->receiver = $poster->id;
        $bonTransactions->comment = 'tip';
        $bonTransactions->post_id = $post->id;
        $bonTransactions->save();
        $poster->notify(new \App\Notifications\NewPostTip('forum', $user->username, $tipAmount, $post));

        return \redirect()->route('forum_topic', ['id' => $post->topic->id])->withSuccess('Your Tip Was Successfully Applied!');
    }

    /**
     * @method getDyingCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getDyingCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('peers')->select('peers.hash')->distinct()->join('torrents', 'torrents.id', '=', 'peers.torrent_id')->where('peers.seeder', 1)->where('torrents.seeders', 1)->where('torrents.times_completed', '>', 2)->where('peers.user_id', $user->id)->count();
    }

    /**
     * @method getLegendaryCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getLegendaryCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('peers')->select('peers.hash')->distinct()->join('torrents', 'torrents.id', '=', 'peers.torrent_id')->whereRaw('torrents.created_at < date_sub(now(), interval 12 month)')->whereRaw('date_sub(peers.created_at,interval 30 minute) < now()')->where('peers.seeder', 1)->where('peers.user_id', $user->id)->count();
    }

    /**
     * @method getOldCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getOldCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('peers')->select('peers.hash')->distinct()->join('torrents', 'torrents.id', '=', 'peers.torrent_id')->whereRaw('torrents.created_at < date_sub(now(), Interval 6 month)')->whereRaw('torrents.created_at > date_sub(now(), interval 12 month)')->whereRaw('date_sub(peers.created_at,interval 30 minute) < now()')->where('peers.seeder', 1)->where('peers.user_id', $user->id)->count();
    }

    /**
     * @method getHugeCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getHugeCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('peers')->select('peers.hash')->distinct()->join('torrents', 'torrents.id', '=', 'peers.torrent_id')->where('peers.seeder', 1)->where('torrents.size', '>=', $this->byteUnits->bytesFromUnit('100GiB'))->where('peers.user_id', $user->id)->count();
    }

    /**
     * @method getLargeCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getLargeCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('peers')->select('peers.hash')->distinct()->join('torrents', 'torrents.id', '=', 'peers.torrent_id')->where('peers.seeder', 1)->where('torrents.size', '>=', $this->byteUnits->bytesFromUnit('25GiB'))->where('torrents.size', '<', $this->byteUnits->bytesFromUnit('100GiB'))->where('peers.user_id', $user->id)->count();
    }

    /**
     * @method getRegularCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getRegularCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('peers')->select('peers.hash')->distinct()->join('torrents', 'torrents.id', '=', 'peers.torrent_id')->where('peers.seeder', 1)->where('torrents.size', '>=', $this->byteUnits->bytesFromUnit('1GiB'))->where('torrents.size', '<', $this->byteUnits->bytesFromUnit('25GiB'))->where('peers.user_id', $user->id)->count();
    }

    /**
     * @method getParticipaintSeedCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getParticipaintSeedCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('history')->select('history.seedtime')->distinct()->join('torrents', 'torrents.info_hash', '=', 'history.info_hash')->where('history.active', 1)->where('history.seedtime', '>=', 2592000)->where('history.seedtime', '<', 2592000 * 2)->where('history.user_id', $user->id)->count();
    }

    /**
     * @method getParticipaintSeedCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getTeamPlayerSeedCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('history')->select('history.seedtime')->distinct()->join('torrents', 'torrents.info_hash', '=', 'history.info_hash')->where('history.active', 1)->where('history.seedtime', '>=', 2592000 * 2)->where('history.seedtime', '<', 2592000 * 3)->where('history.user_id', $user->id)->count();
    }

    /**
     * @method getParticipaintSeedCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getCommitedSeedCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('history')->select('history.seedtime')->distinct()->join('torrents', 'torrents.info_hash', '=', 'history.info_hash')->where('history.active', 1)->where('history.seedtime', '>=', 2592000 * 3)->where('history.seedtime', '<', 2592000 * 6)->where('history.user_id', $user->id)->count();
    }

    /**
     * @method getParticipaintSeedCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getMVPSeedCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('history')->select('history.seedtime')->distinct()->join('torrents', 'torrents.info_hash', '=', 'history.info_hash')->where('history.active', 1)->where('history.seedtime', '>=', 2592000 * 6)->where('history.seedtime', '<', 2592000 * 12)->where('history.user_id', $user->id)->count();
    }

    /**
     * @method getParticipaintSeedCount
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return int
     */
    public function getLegendarySeedCount(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return \Illuminate\Support\Facades\DB::table('history')->select('history.seedtime')->distinct()->join('torrents', 'torrents.info_hash', '=', 'history.info_hash')->where('history.active', 1)->where('history.seedtime', '>=', 2592000 * 12)->where('history.user_id', $user->id)->count();
    }
}
