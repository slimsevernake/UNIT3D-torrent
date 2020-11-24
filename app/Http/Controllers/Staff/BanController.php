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

namespace App\Http\Controllers\Staff;

use App\Models\Ban;
use App\Models\Group;
use App\Models\User;

/**
 * @see \Tests\Todo\Feature\Http\Controllers\Staff\BanControllerTest
 */
class BanController extends \App\Http\Controllers\Controller
{
    /**
     * Display All Bans.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $bans = \App\Models\Ban::latest()->paginate(25);

        return \view('Staff.ban.index', ['bans' => $bans]);
    }

    /**
     * Ban A User (current_group -> banned).
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User         $username
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store(\Illuminate\Http\Request $request, User $username)
    {
        $user = \App\Models\User::where('username', '=', $username)->firstOrFail();
        $staff = $request->user();
        $bannedGroup = \cache()->rememberForever('banned_group', fn () => \App\Models\Group::where('slug', '=', 'banned')->pluck('id'));
        \abort_if($user->group->is_modo || $request->user()->id == $user->id, 403);
        $user->group_id = $bannedGroup[0];
        $user->can_upload = 0;
        $user->can_download = 0;
        $user->can_comment = 0;
        $user->can_invite = 0;
        $user->can_request = 0;
        $user->can_chat = 0;
        $ban = new \App\Models\Ban();
        $ban->owned_by = $user->id;
        $ban->created_by = $staff->id;
        $ban->ban_reason = $request->input('ban_reason');
        $v = \validator($ban->toArray(), ['ban_reason' => 'required']);
        if ($v->fails()) {
            return \redirect()->route('users.show', ['username' => $user->username])->withErrors($v->errors());
        }
        $user->save();
        $ban->save();
        // Send Email
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\BanUser($user->email, $ban));

        return \redirect()->route('users.show', ['username' => $user->username])->withSuccess('User Is Now Banned!');
    }

    /**
     * Unban A User (banned -> new_group).
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User         $username
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(\Illuminate\Http\Request $request, User $username)
    {
        $user = \App\Models\User::where('username', '=', $username)->firstOrFail();
        $staff = $request->user();
        \abort_if($user->group->is_modo || $request->user()->id == $user->id, 403);
        $user->group_id = $request->input('group_id');
        $user->can_upload = 1;
        $user->can_download = 1;
        $user->can_comment = 1;
        $user->can_invite = 1;
        $user->can_request = 1;
        $user->can_chat = 1;
        $ban = new \App\Models\Ban();
        $ban->owned_by = $user->id;
        $ban->created_by = $staff->id;
        $ban->unban_reason = $request->input('unban_reason');
        $ban->removed_at = \Carbon\Carbon::now();
        $v = \validator($request->all(), ['group_id' => 'required', 'unban_reason' => 'required']);
        if ($v->fails()) {
            return \redirect()->route('users.show', ['username' => $user->username])->withErrors($v->errors());
        }
        $user->save();
        $ban->save();
        // Send Email
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\UnbanUser($user->email, $ban));

        return \redirect()->route('users.show', ['username' => $user->username])->withSuccess('User Is Now Relieved Of His Ban!');
    }
}
