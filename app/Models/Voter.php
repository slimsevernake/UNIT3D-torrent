<?php
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

namespace App\Models;

/**
 * App\Models\Voter.
 *
 * @property int                             $id
 * @property int                             $poll_id
 * @property int                             $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Poll $poll
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter wherePollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voter whereUserId($value)
 * @mixin \Eloquent
 */
class Voter extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \App\Traits\Auditable;

    /**
     * Belongs To A Poll.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function poll()
    {
        return $this->belongsTo(\App\Models\Poll::class);
    }

    /**
     * Belongs To A User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class)->withDefault(['username' => 'System', 'id' => '1']);
    }
}
