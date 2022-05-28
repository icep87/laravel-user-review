<?php

namespace DGvai\Review;

use DGvai\Review\Review;
use Illuminate\Support\Facades\DB;

trait Reviewable
{
    public function reviews()
    {
        return $this->morphMany(Review::class, 'model');
    }

    public function averageRating($star = null)
    {
        if (!is_null($star)) {
            $quantity = $this->reviews()->where('active', 1)->count();
            $total = $this->reviews()->where('active', 1)->sum('rating');

            return ($quantity * $star) > 0 ? $total / (($quantity * $star) / 100) : 0;
        } else {
            return number_format((float)$this->reviews()->where('active', 1)->avg('rating'), 2);
        }
    }

    public function userRating($user)
    {
        return $this->reviews()->where('active', 1)->where('user_id', $user->id)->avg('rating');
    }

    public function makeReview($user, $rating, $review = null, $reviewTitle = null)
    {
        return $this->reviews()->create([
            'review_title' => $reviewTitle,
            'review' => $review,
            'rating' => $rating,
            'user_id' => $user->id
        ]);
    }

    public function makeOrUpdateReview($user, $rating, $review = null, $reviewTitle = null)
    {
        $criteria = ["user_id" => $user->id];
        $payLoad = [
            'review_title' => $reviewTitle,
            'review' => $review,
            'rating' => $rating,
            'user_id' => $user->id
        ];
        $review = $this->reviews()->updateOrCreate($criteria, $payLoad);
        return $review;
    }

    public function getRatingAttribute()
    {
        return ($this->averageRating() == '0.00')
            ? config('user-review.fake.enabled')
            ? config('user-review.fake.star')
            : $this->averageRating()
            : $this->averageRating();
    }

    public function getRatingPercentAttribute()
    {
        return $this->averageRating(config('user-review.star'));
    }

    public function filter(int $rating)
    {
        return $this->reviews()->where('active', 1)->where('rating', $rating)->get();
    }

    public function filteredPercentage(int $rating)
    {
        $count = $this->reviews()->where('active', 1)->where('rating', $rating)->count();
        $total = $this->reviews()->where('active', 1)->count();
        return ($total == 0) ? 0 : ($count / $total) * 100;
    }

    public function getRatingCountByStar(int $rating)
    {
        return $this->reviews()->where('active', 1)->where('rating', $rating)->count();
    }

    public function getRatingStarAttribute()
    {
        $allReviews = $this->reviews()->where('active', 1)->get();
        $counted = $allReviews->mapWithKeys(function ($item) {
            for ($i = 1; $i <= config('user-review.star'); $i++) {
                $rating[$i] = $item->where('rating', $i)->count();
            }
            return $rating;
        });
        return $counted;
    }

    public function getTotalRatingCount()
    {
        return $this->reviews()->where('active', 1)->count();
    }
}
