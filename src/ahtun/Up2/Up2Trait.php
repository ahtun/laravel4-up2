<?php namespace ahtun\Up2;

trait Up2Trait {

    /**
     * Boot the Up2 trait for a model.
     *
     * @return void
     */
    // public static function bootUp2Trait()
    // {
    //     static::addGlobalScope(new Up2Scope);
    // }

    /**
     * Attachment relation.
     *
     * @return Attachment
     */
    public function attachments()
    {
        $attachmentsModel = \Config::get('up2::attachments.model');

        return $this->morphToMany($attachmentsModel, 'attachmentable');
    }

}