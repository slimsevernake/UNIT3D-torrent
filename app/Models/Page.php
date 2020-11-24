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

namespace App\Models;

use App\Helpers\Markdown;

/**
 * App\Models\Page.
 *
 * @property int                             $id
 * @property string|null                     $name
 * @property string|null                     $slug
 * @property string|null                     $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Page whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Page extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \App\Traits\Auditable;

    /**
     * Set The Pages Content After Its Been Purified.
     *
     * @param string $value
     *
     * @return void
     */
    public function setContentAttribute(string $value)
    {
        $this->attributes['content'] = $value;
    }

    /**
     * Parse Content And Return Valid HTML.
     *
     * @return string Convert BBCODE and Parse Markdown To HTML
     */
    public function getContentHtml()
    {
        $bbCodeConverter = new \App\Helpers\BBCodeConverter($this->content);
        $content = $bbCodeConverter->toMarkdown();
        $markdown = new \App\Helpers\Markdown();

        return $markdown->text($content);
    }
}
