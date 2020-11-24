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

namespace App\Http\Controllers\API;

use App\Repositories\ChatRepository;
use Illuminate\Auth\AuthManager;

/**
 * @see \Tests\Feature\Http\Controllers\API\ChatControllerTest
 */
class ChatController extends \App\Http\Controllers\Controller
{
    /**
     * @var ChatRepository
     */
    private ChatRepository $chatRepository;
    /**
     * @var AuthManager
     */
    private $authManager;

    public function __construct(\App\Repositories\ChatRepository $chatRepository, \Illuminate\Contracts\Auth\Factory $authFactory)
    {
        $this->chatRepository = $chatRepository;
        $this->authManager = $authFactory;
    }

    /* STATUSES */
    public function statuses()
    {
        return \response($this->chatRepository->statuses());
    }

    /* ECHOES */
    public function echoes()
    {
        $user = \App\Models\User::with(['echoes'])->findOrFail($this->authManager->user()->id);
        if (! $user->echoes || (\is_countable($user->echoes->toArray()) ? \count($user->echoes->toArray()) : 0) < 1) {
            $userEcho = new \App\Models\UserEcho();
            $userEcho->user_id = $this->authManager->user()->id;
            $userEcho->room_id = 1;
            $userEcho->save();
        }

        return \App\Http\Resources\UserEchoResource::collection($this->chatRepository->echoes($this->authManager->user()->id));
    }

    /* AUDIBLES */
    public function audibles()
    {
        $user = \App\Models\User::with(['audibles'])->findOrFail($this->authManager->user()->id);
        if (! $user->audibles || (\is_countable($user->audibles->toArray()) ? \count($user->audibles->toArray()) : 0) < 1) {
            $userAudible = new \App\Models\UserAudible();
            $userAudible->user_id = $this->authManager->user()->id;
            $userAudible->room_id = 1;
            $userAudible->status = 1;
            $userAudible->save();
        }

        return \App\Http\Resources\UserAudibleResource::collection($this->chatRepository->audibles($this->authManager->user()->id));
    }

    /* BOTS */
    public function bots()
    {
        return \App\Http\Resources\BotResource::collection($this->chatRepository->bots());
    }

    /* ROOMS */
    public function rooms()
    {
        return \App\Http\Resources\ChatRoomResource::collection($this->chatRepository->rooms());
    }

    public function config()
    {
        return \response($this->chatRepository->config());
    }

    /* MESSAGES */
    public function messages($roomId)
    {
        return \App\Http\Resources\ChatMessageResource::collection($this->chatRepository->messages($roomId));
    }

    /* MESSAGES */
    public function privateMessages($targetId)
    {
        return \App\Http\Resources\ChatMessageResource::collection($this->chatRepository->privateMessages($this->authManager->user()->id, $targetId));
    }

    /* MESSAGES */
    public function botMessages($botId)
    {
        $bot = \App\Models\Bot::where('id', '=', $botId)->firstOrFail();
        if ($bot->is_systembot) {
            $runbot = new \App\Bots\SystemBot($this->chatRepository);
        } elseif ($bot->is_nerdbot) {
            $runbot = new \App\Bots\NerdBot($this->chatRepository);
        }
        $runbot->process('message', $this->authManager->user(), '', 0);

        return \App\Http\Resources\ChatMessageResource::collection($this->chatRepository->botMessages($this->authManager->user()->id, $bot->id));
    }

    public function createMessage(\Illuminate\Http\Request $request)
    {
        $user = $this->authManager->user();
        $userId = $user->id;
        $receiverId = $request->input('receiver_id');
        $roomId = $request->input('chatroom_id');
        $botId = $request->input('bot_id');
        $message = $request->input('message');
        $targeted = $request->input('targeted');
        $save = $request->get('save');
        if ($user->can_chat === 0) {
            return \response('error', 401);
        }
        // Temp Fix For HTMLPurifier
        if ($message === '<') {
            return \response('error', 401);
        }
        $botDirty = 0;
        $bots = \cache()->get('bots');
        if (! $bots || ! \is_array($bots) || \count($bots) < 1) {
            $bots = \App\Models\Bot::where('active', '=', 1)->orderBy('position', 'asc')->get();
            $botDirty = 1;
        }
        if ($botDirty == 1) {
            $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
            \cache()->put('bots', $bots, $expiresAt);
        }
        $which = null;
        $target = null;
        $runbot = null;
        $trip = 'msg';
        if ($message && \substr($message, 0, 1 + \strlen($trip)) === '/'.$trip) {
            $which = 'skip';
            $command = @\explode(' ', $message);
            if (\array_key_exists(1, $command)) {
                $receiver = \App\Models\User::where('username', 'like', $command[1])->firstOrFail();
                $receiverId = $receiver->id;
                $clone = $command;
                \array_shift($clone);
                \array_shift($clone);
                $message = \trim(\implode(' ', $clone));
            }
            $botId = 1;
        }
        $trip = 'gift';
        if ($message && \substr($message, 0, 1 + \strlen($trip)) === '/'.$trip) {
            $which = 'echo';
            $target = 'system';
            $message = '/bot gift'.\substr($message, \strlen($trip) + 1, \strlen($message));
        }
        if ($target === 'system') {
            $runbot = new \App\Bots\SystemBot($this->chatRepository);
        }
        if ($which == null) {
            foreach ($bots as $bot) {
                if ($message && \substr($message, 0, 1 + \strlen($bot->command)) === '/'.$bot->command) {
                    $which = 'echo';
                } elseif ($message && \substr($message, 0, 1 + \strlen($bot->command)) === '!'.$bot->command) {
                    $which = 'public';
                } elseif ($message && \substr($message, 0, 1 + \strlen($bot->command)) === '@'.$bot->command) {
                    $message = \substr($message, 1 + \strlen($bot->command), \strlen($message));
                    $which = 'private';
                } elseif ($message && $receiverId == 1 && $bot->id == $botId) {
                    if ($message && \substr($message, 0, 1 + \strlen($bot->command)) === '/'.$bot->command) {
                        $message = \substr($message, 1 + \strlen($bot->command), \strlen($message));
                    }
                    if ($message && \substr($message, 0, 1 + \strlen($bot->command)) === '!'.$bot->command) {
                        $message = \substr($message, 1 + \strlen($bot->command), \strlen($message));
                    }
                    if ($message && \substr($message, 0, 1 + \strlen($bot->command)) === '@'.$bot->command) {
                        $message = \substr($message, 1 + \strlen($bot->command), \strlen($message));
                    }
                    $which = 'message';
                }
                if ($which != null) {
                    break;
                }
            }
        }
        if ($which != null && $which !== 'skip' && ! $runbot) {
            if ($bot->is_systembot) {
                $runbot = new \App\Bots\SystemBot($this->chatRepository);
            } elseif ($bot->is_nerdbot) {
                $runbot = new \App\Bots\NerdBot($this->chatRepository);
            } elseif ($bot->is_casinobot) {
                $runbot = new \App\Bots\CasinoBot($this->chatRepository);
            }
        }
        if ($runbot !== null) {
            return $runbot->process($which, $this->authManager->user(), $message, 0);
        }
        $echo = false;
        if ($receiverId && $receiverId > 0) {
            $senderDirty = 0;
            $receiverDirty = 0;
            $senderEchoes = \cache()->get('user-echoes'.$userId);
            $receiverEchoes = \cache()->get('user-echoes'.$receiverId);
            if (! $senderEchoes || ! \is_array($senderEchoes) || (\is_countable($senderEchoes) ? \is_countable($senderEchoes) ? \count($senderEchoes) : 0 : 0) < 1) {
                $senderEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
            }
            if (! $receiverEchoes || ! \is_array($receiverEchoes) || (\is_countable($receiverEchoes) ? \is_countable($receiverEchoes) ? \count($receiverEchoes) : 0 : 0) < 1) {
                $receiverEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$receiverId])->get();
            }
            $senderListening = false;
            foreach ($senderEchoes as $se => $senderEcho) {
                if ($senderEcho['target_id'] == $receiverId) {
                    $senderListening = true;
                }
            }
            if (! $senderListening) {
                $senderPort = new \App\Models\UserEcho();
                $senderPort->user_id = $userId;
                $senderPort->target_id = $receiverId;
                $senderPort->save();
                $senderEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
                $senderDirty = 1;
            }
            $receiverListening = false;
            foreach ($receiverEchoes as $se => $receiverEcho) {
                if ($receiverEcho['target_id'] == $userId) {
                    $receiverListening = true;
                }
            }
            if (! $receiverListening) {
                $receiverPort = new \App\Models\UserEcho();
                $receiverPort->user_id = $receiverId;
                $receiverPort->target_id = $userId;
                $receiverPort->save();
                $receiverEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$receiverId])->get();
                $receiverDirty = 1;
            }
            if ($senderDirty == 1) {
                $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
                \cache()->put('user-echoes'.$userId, $senderEchoes, $expiresAt);
                \event(new \App\Events\Chatter('echo', $userId, \App\Http\Resources\UserEchoResource::collection($senderEchoes)));
            }
            if ($receiverDirty == 1) {
                $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
                \cache()->put('user-echoes'.$receiverId, $receiverEchoes, $expiresAt);
                \event(new \App\Events\Chatter('echo', $receiverId, \App\Http\Resources\UserEchoResource::collection($receiverEchoes)));
            }
            $senderDirty = 0;
            $receiverDirty = 0;
            $senderAudibles = \cache()->get('user-audibles'.$userId);
            $receiverAudibles = \cache()->get('user-audibles'.$receiverId);
            if (! $senderAudibles || ! \is_array($senderAudibles) || (\is_countable($senderAudibles) ? \is_countable($senderAudibles) ? \count($senderAudibles) : 0 : 0) < 1) {
                $senderAudibles = \App\Models\UserAudible::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
            }
            if (! $receiverAudibles || ! \is_array($receiverAudibles) || (\is_countable($receiverAudibles) ? \is_countable($receiverAudibles) ? \count($receiverAudibles) : 0 : 0) < 1) {
                $receiverAudibles = \App\Models\UserAudible::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$receiverId])->get();
            }
            $senderListening = false;
            foreach ($senderAudibles as $se => $senderEcho) {
                if ($senderEcho['target_id'] == $receiverId) {
                    $senderListening = true;
                }
            }
            if (! $senderListening) {
                $senderPort = new \App\Models\UserAudible();
                $senderPort->user_id = $userId;
                $senderPort->target_id = $receiverId;
                $senderPort->status = 0;
                $senderPort->save();
                $senderAudibles = \App\Models\UserAudible::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
                $senderDirty = 1;
            }
            $receiverListening = false;
            foreach ($receiverAudibles as $se => $receiverEcho) {
                if ($receiverEcho['target_id'] == $userId) {
                    $receiverListening = true;
                }
            }
            if (! $receiverListening) {
                $receiverPort = new \App\Models\UserAudible();
                $receiverPort->user_id = $receiverId;
                $receiverPort->target_id = $userId;
                $receiverPort->status = 0;
                $receiverPort->save();
                $receiverAudibles = \App\Models\UserAudible::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$receiverId])->get();
                $receiverDirty = 1;
            }
            if ($senderDirty == 1) {
                $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
                \cache()->put('user-audibles'.$userId, $senderAudibles, $expiresAt);
                \event(new \App\Events\Chatter('audible', $userId, \App\Http\Resources\UserAudibleResource::collection($senderAudibles)));
            }
            if ($receiverDirty == 1) {
                $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
                \cache()->put('user-audibles'.$receiverId, $receiverAudibles, $expiresAt);
                \event(new \App\Events\Chatter('audible', $receiverId, \App\Http\Resources\UserAudibleResource::collection($receiverAudibles)));
            }
            $roomId = 0;
            $ignore = $botId > 0 && $receiverId == 1 ? true : null;
            $save = true;
            $echo = true;
            $message = $this->chatRepository->privateMessage($userId, $message, $receiverId, null, $ignore);
        } else {
            $receiverId = null;
            $botId = null;
            $message = $this->chatRepository->message($userId, $roomId, $message, $receiverId, $botId);
        }
        if (! $save) {
            $message->delete();
        }
        if ($save && $echo) {
            return new \App\Http\Resources\ChatMessageResource($message);
        }

        return \response('success');
    }

    public function deleteMessage($id)
    {
        $this->chatRepository->deleteMessage($id);

        return \response('success');
    }

    public function deleteRoomEcho(\Illuminate\Http\Request $request, $userId)
    {
        $echo = \App\Models\UserEcho::where('user_id', '=', $userId)->where('room_id', '=', $request->input('room_id'))->firstOrFail();
        $echo->delete();
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'echoes'])->findOrFail($userId);
        $room = $this->chatRepository->roomFindOrFail($request->input('room_id'));
        $user->chatroom()->dissociate();
        $user->chatroom()->associate($room);
        $user->save();
        $senderEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
        $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
        \cache()->put('user-echoes'.$userId, $senderEchoes, $expiresAt);
        \event(new \App\Events\Chatter('echo', $userId, \App\Http\Resources\UserEchoResource::collection($senderEchoes)));

        return \response($user);
    }

    public function deleteTargetEcho(\Illuminate\Http\Request $request, $userId)
    {
        $echo = \App\Models\UserEcho::where('user_id', '=', $userId)->where('target_id', '=', $request->input('target_id'))->firstOrFail();
        $echo->delete();
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'echoes'])->findOrFail($userId);
        $senderEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
        $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
        \cache()->put('user-echoes'.$userId, $senderEchoes, $expiresAt);
        \event(new \App\Events\Chatter('echo', $userId, \App\Http\Resources\UserEchoResource::collection($senderEchoes)));

        return \response($user);
    }

    public function deleteBotEcho(\Illuminate\Http\Request $request, $userId)
    {
        $echo = \App\Models\UserEcho::where('user_id', '=', $userId)->where('bot_id', '=', $request->input('bot_id'))->firstOrFail();
        $echo->delete();
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'echoes'])->findOrFail($userId);
        $senderEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
        $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
        \cache()->put('user-echoes'.$userId, $senderEchoes, $expiresAt);
        \event(new \App\Events\Chatter('echo', $userId, \App\Http\Resources\UserEchoResource::collection($senderEchoes)));

        return \response($user);
    }

    public function toggleRoomAudible(\Illuminate\Http\Request $request, $userId)
    {
        $echo = \App\Models\UserAudible::where('user_id', '=', $userId)->where('room_id', '=', $request->input('room_id'))->firstOrFail();
        $echo->status = $echo->status == 1 ? 0 : 1;
        $echo->save();
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'audibles', 'audibles'])->findOrFail($userId);
        $senderAudibles = \App\Models\UserAudible::with(['room', 'target', 'bot'])->where('user_id', $userId)->get();
        $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
        \cache()->put('user-audibles'.$userId, $senderAudibles, $expiresAt);
        \event(new \App\Events\Chatter('audible', $userId, \App\Http\Resources\UserAudibleResource::collection($senderAudibles)));

        return \response($user);
    }

    public function toggleTargetAudible(\Illuminate\Http\Request $request, $userId)
    {
        $echo = \App\Models\UserAudible::where('user_id', '=', $userId)->where('target_id', '=', $request->input('target_id'))->firstOrFail();
        $echo->status = $echo->status == 1 ? 0 : 1;
        $echo->save();
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'audibles', 'audibles'])->findOrFail($userId);
        $senderAudibles = \App\Models\UserAudible::with(['target', 'room', 'bot'])->where('user_id', $userId)->get();
        $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
        \cache()->put('user-audibles'.$userId, $senderAudibles, $expiresAt);
        \event(new \App\Events\Chatter('audible', $userId, \App\Http\Resources\UserAudibleResource::collection($senderAudibles)));

        return \response($user);
    }

    public function toggleBotAudible(\Illuminate\Http\Request $request, $userId)
    {
        $echo = \App\Models\UserAudible::where('user_id', '=', $userId)->where('bot_id', '=', $request->input('bot_id'))->firstOrFail();
        $echo->status = $echo->status == 1 ? 0 : 1;
        $echo->save();
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'audibles', 'audibles'])->findOrFail($userId);
        $senderAudibles = \App\Models\UserAudible::with(['bot', 'room', 'bot'])->where('user_id', $userId)->get();
        $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
        \cache()->put('user-audibles'.$userId, $senderAudibles, $expiresAt);
        \event(new \App\Events\Chatter('audible', $userId, \App\Http\Resources\UserAudibleResource::collection($senderAudibles)));

        return \response($user);
    }

    /* USERS */
    public function updateUserChatStatus(\Illuminate\Http\Request $request, $id)
    {
        $systemUser = \App\Models\User::where('username', 'System')->firstOrFail();
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'echoes'])->findOrFail($id);
        $status = $this->chatRepository->statusFindOrFail($request->input('status_id'));
        $log = '[url=/users/'.$user->username.']'.$user->username.'[/url] has updated their status to [b]'.$status->name.'[/b]';
        $message = $this->chatRepository->message($systemUser->id, $user->chatroom->id, $log, null);
        $message->save();
        $user->chatStatus()->dissociate();
        $user->chatStatus()->associate($status);
        $user->save();

        return \response($user);
    }

    public function updateUserRoom(\Illuminate\Http\Request $request, $id)
    {
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'echoes'])->findOrFail($id);
        $room = $this->chatRepository->roomFindOrFail($request->input('room_id'));
        $user->chatroom()->dissociate();
        $user->chatroom()->associate($room);
        $user->save();
        $senderDirty = 0;
        $senderEchoes = \cache()->get('user-echoes'.$id);
        if (! $senderEchoes || ! \is_array($senderEchoes) || (\is_countable($senderEchoes) ? \is_countable($senderEchoes) ? \count($senderEchoes) : 0 : 0) < 1) {
            $senderEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$id])->get();
        }
        $senderListening = false;
        foreach ($senderEchoes as $se => $senderEcho) {
            if ($senderEcho['room_id'] == $room->id) {
                $senderListening = true;
            }
        }
        if (! $senderListening) {
            $userEcho = new \App\Models\UserEcho();
            $userEcho->user_id = $id;
            $userEcho->room_id = $room->id;
            $userEcho->save();
            $senderEchoes = \App\Models\UserEcho::with(['room', 'target', 'bot'])->whereRaw('user_id = ?', [$id])->get();
            $senderDirty = 1;
        }
        if ($senderDirty == 1) {
            $expiresAt = \Carbon\Carbon::now()->addMinutes(60);
            \cache()->put('user-echoes'.$id, $senderEchoes, $expiresAt);
            \event(new \App\Events\Chatter('echo', $id, \App\Http\Resources\UserEchoResource::collection($senderEchoes)));
        }

        return \response($user);
    }

    public function updateUserTarget($id)
    {
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'echoes'])->findOrFail($id);

        return \response($user);
    }

    public function updateBotTarget($id)
    {
        $user = \App\Models\User::with(['chatStatus', 'chatroom', 'group', 'echoes'])->findOrFail($id);

        return \response($user);
    }
}
