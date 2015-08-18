<?php namespace ahtun\Up2\Attachments\Eloquent;

use Illuminate\Database\Eloquent\Model;
use ahtun\Up2\Attachments\AttachmentInterface;

class Attachment extends Model implements AttachmentInterface {

    /**
     * DB table.
     *
     * @var string
     */
    protected $table = 'attachments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Model event.
     *
     * @return void
     */
    public static function boot()
    {
        // Delete relate after delete attachment.
        static::deleted(function($attachment)
        {
            \DB::table('attachmentables')->where('attachment_id', $attachment->id)->delete();
        });
    }

    /**
     * Method to save data to db.
     *
     * You can pass extra parameter by extending this class
     * and change config attachment model.
     *
     * @param Attachment $result
     */
    public function add($result)
    {
        // If you want to add something else.
        // $result = array_merge($result, array(
        //     'user_id' => Auth::user()->id
        // ));

        //$attachment = new static();

        $this->fill($result);

        return $this->save();
    }

    /**
     * Attachment has many relates.
     *
     * @return object
     */
    public function attachmentable()
    {
        $attachmentModel = \Config::get('up2::attachments.model');

        if ( ! $attachmentModel)
        {
            $attachmentModel = '\ahtun\Up2\Attachments\Eloquent\Attachment';
        }

        return $this->morphToMany($attachmentModel, 'attachmentable', 'attachmentables', 'attachment_id')
                    ->orWhereRaw('attachmentables.attachmentable_type IS NOT NULL');
    }

}