<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinalExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'final_exam_id' => $this->final_exam_id,
            'type' => $this->type,
            'question' => $this->question_en,
            'question_ar' => $this->question_ar,
            'question_en' => $this->question_en,
            'options' => $this->options,
            'correct_answer' => $this->correct_answer,
            'model_answer' => $this->correct_answer,
            'points' => $this->points,
            'order' => $this->order,
        ];
    }
}
