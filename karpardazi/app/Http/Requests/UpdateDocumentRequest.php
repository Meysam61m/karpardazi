<?php

namespace App\Http\Requests;

class UpdateDocumentRequest extends StoreDocumentRequest
{
    // قواعد ویرایش با ثبت یکسان است؛ شماره سند هرگز از فرم ویرایش نمی‌شود.
}
