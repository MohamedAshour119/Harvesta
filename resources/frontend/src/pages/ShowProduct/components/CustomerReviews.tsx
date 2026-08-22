import React, { useState, useEffect } from 'react';
import { StarRating } from '../../../components/StarRating';
import ApiClient from '../../../ApiClient';
import { useSelector } from 'react-redux';
import type { RootState } from '../../../store/store';
import { RiArrowLeftSLine, RiArrowRightSLine } from 'react-icons/ri';
import { MdKeyboardDoubleArrowLeft, MdKeyboardDoubleArrowRight } from 'react-icons/md';
import i18n from '../../../i18n';
import type { Review, VariantRatingData } from '../../../types';
import { useTranslation } from 'react-i18next';

interface PaginatedReviewsResponse {
  ratings: Review[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
}

interface CustomerReviewsProps {
  variantRatingData: VariantRatingData | null;
  productId: number | null;
  onRatingSubmitted?: () => void;
}

export const CustomerReviews: React.FC<CustomerReviewsProps> = ({
  variantRatingData,
  productId,
  onRatingSubmitted,
}) => {
  const [newReviewRating, setNewReviewRating] = useState<number | null>(null);
  const [newReviewComment, setNewReviewComment] = useState('');
  const [userRating, setUserRating] = useState<{ rating: number | null; comment: string } | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [productRating, setProductRating] = useState({
    average_rating: variantRatingData?.average_rating || null,
    total_ratings: variantRatingData?.total_ratings || null,
    rating_breakdown: variantRatingData?.rating_breakdown || null,
  });
  const [error, setError] = useState<string | null>(null);
  const [currentRatingStats, setCurrentRatingStats] = useState<VariantRatingData | null>(null);

  // State for paginated reviews
  const [reviews, setReviews] = useState<Review[]>([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loadingReviews, setLoadingReviews] = useState(false);

  const isAuth = useSelector((state: RootState) => state?.auth?.isAuth);
  const { t } = useTranslation(['showProduct']);
  const lang = i18n.language;

  useEffect(() => {
    setCurrentRatingStats(variantRatingData);
  }, [variantRatingData]);

  useEffect(() => {
    if (isAuth && productId) {
      fetchUserRating();
    } else {
      setUserRating(null);
      setNewReviewRating(null);
      setNewReviewComment('');
    }
  }, [isAuth, productId]);

  useEffect(() => {
    if (productId) {
      fetchPaginatedReviews(currentPage);
    }
  }, [productId, currentPage]);

  const fetchPaginatedReviews = async (page: number) => {
    if (!productId) return;
    setLoadingReviews(true);
    try {
      const response = await ApiClient.ratings.getPaginatedRatings(productId, page, 5);
      if (response.data.success) {
        const paginatedData = response.data.data as PaginatedReviewsResponse;
        setReviews(paginatedData.ratings);
        setCurrentPage(paginatedData.pagination.current_page);
        setLastPage(paginatedData.pagination.last_page);
      }
    } catch (err) {
      console.error('Failed to fetch reviews:', err);
      setError('Failed to load reviews.');
    } finally {
      setLoadingReviews(false);
    }
  };

  const fetchUserRating = async () => {
    if (!productId) return;
    try {
      const response = await ApiClient.ratings.getUserRating(productId);
      if (response.data.success && response.data.data.user_rating) {
        const userRatingData = response.data.data.user_rating;
        setUserRating(userRatingData);
        setNewReviewRating(userRatingData.rating);
        setNewReviewComment(userRatingData.comment || '');
      }
    } catch (err: any) {
      console.log('No existing user rating found');
    }
  };

  const handleRatingSubmit = async (newRating: number) => {
    setNewReviewRating(newRating);

    if (!isAuth || !productId) {
      setError('Please log in to submit a rating.');
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      const response = await ApiClient.ratings.submitRating(
        productId,
        newRating,
        userRating?.comment || newReviewComment.trim()
      );

      if (response.data.success) {
        setUserRating({
          rating: newRating,
          comment: userRating?.comment || newReviewComment.trim(),
        });

        const productRatingResponse = response.data.data.stats;
        setProductRating(productRatingResponse);

        if (response.data.data?.rating) {
          const updatedStats = response.data.data.stats;
          setCurrentRatingStats({
            ratings: currentRatingStats?.ratings || [],
            average_rating: updatedStats.average_rating,
            total_ratings: updatedStats.total_ratings,
            rating_breakdown: updatedStats.rating_breakdown,
          });

          const event = new CustomEvent('ratingUpdated', { detail: updatedStats });
          document.dispatchEvent(event);
        }

        if (onRatingSubmitted) {
          onRatingSubmitted();
        }
        fetchPaginatedReviews(1);
      } else {
        setError(response.data.message || 'Failed to submit rating.');
      }
    } catch (err: any) {
      console.error('Failed to submit rating on click:', err);
      setError('An unexpected error occurred.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleSubmitReview = async () => {
    if (!newReviewComment.trim() || !productId) {
      setError('Please provide a comment for your review.');
      return;
    }

    if (!isAuth) {
      setError('Please log in to submit a review.');
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      const response = await ApiClient.ratings.submitRating(
        productId,
        newReviewRating,
        newReviewComment.trim()
      );

      if (response.data.success) {
        setNewReviewComment('');

        setUserRating({
          rating: newReviewRating,
          comment: newReviewComment.trim(),
        });

        if (response.data.data?.variant_stats?.original?.data) {
          const updatedStats = response.data.data.variant_stats.original.data;
          setCurrentRatingStats({
            ratings: currentRatingStats?.ratings || [],
            average_rating: updatedStats.average_rating,
            total_ratings: updatedStats.total_ratings,
            rating_breakdown: updatedStats.rating_breakdown,
          });

          const event = new CustomEvent('ratingUpdated', { detail: updatedStats });
          document.dispatchEvent(event);
        }

        if (onRatingSubmitted) {
          onRatingSubmitted();
        }

        fetchPaginatedReviews(1);
      } else {
        setError(response.data.message || 'Failed to submit review.');
      }
    } catch (err: any) {
      console.error('Failed to submit review:', err);
      setError('An unexpected error occurred.');
    } finally {
      setSubmitting(false);
    }
  };

  const ratingStats = currentRatingStats;

  if (!ratingStats) {
    return (
      <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl p-6 space-y-8 shadow-sm">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-slate-100">{t('showProduct:reviews.customerReviews')}</h2>
        <div className="text-center py-8">
          <p className="text-gray-500 dark:text-slate-400">{t('showProduct:reviews.selectVariant')}</p>
        </div>
      </div>
    );
  }

  const getInitials = (name: string) => {
    if (!name || typeof name !== 'string') {
      return '??';
    }
    return name
      .split(' ')
      .map(word => word.charAt(0).toUpperCase())
      .join('')
      .slice(0, 2);
  };

  const formatDate = (dateString: string) => {
    const locale = lang === 'ar' ? 'ar-EG' : 'en-US';
    return new Date(dateString).toLocaleDateString(locale, {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  return (
    <div className="bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-800 rounded-2xl p-6 sm:p-8 space-y-8 shadow-sm">
      <h2 className="text-2xl font-bold text-gray-900 dark:text-slate-100">{t('showProduct:reviews.customerReviews')}</h2>

      {error && (
        <div className="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl p-4">
          <p className="text-red-600 dark:text-red-400 text-sm">{error}</p>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 gap-x-20">
        {/* Left Side - Overall Rating & Breakdown */}
        <div className="space-y-6">
          <div>
            <h3 className="text-lg font-semibold text-gray-800 dark:text-slate-200 mb-2">{t('showProduct:reviews.overallRating')}</h3>
            <div className="flex items-center space-x-3">
              <span className="text-3xl font-extrabold text-gray-900 dark:text-white">
                {Math.round(productRating.average_rating || 0)}
              </span>
              <StarRating
                rating={Math.round(productRating.average_rating || 0)}
                size="lg"
                allowHalf={false}
              />
            </div>
            <p className="text-sm text-gray-500 dark:text-slate-400 mt-1">
              {t('showProduct:reviews.basedOn')} {ratingStats.total_ratings} {ratingStats.rating_breakdown.length > 1 ? t('showProduct:reviews.ratings') : t('showProduct:reviews.rating')}
            </p>
          </div>

          {/* Rating Breakdown */}
          {productRating?.rating_breakdown && productRating?.rating_breakdown?.length > 0 && (
            <div className="space-y-3">
              {productRating?.rating_breakdown?.map((breakdown) => (
                <div key={breakdown.stars} className="flex items-center space-x-3">
                  <span className="text-sm font-medium text-gray-700 dark:text-slate-300 w-2">{breakdown.stars}</span>
                  <div className="flex-1 bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                    <div
                      className="bg-[#B12C2E] dark:bg-[#E03E41] h-2 rounded-full transition-all duration-300"
                      style={{ width: `${breakdown.percentage}%` }}
                    ></div>
                  </div>
                  <span className="text-sm text-gray-500 dark:text-slate-400 w-10">{breakdown.percentage}%</span>
                </div>
              ))}
            </div>
          )}

          {/* Write a Review */}
          <div className="border-t pt-6 border-gray-100 dark:border-slate-800">
            <h3 className="text-lg font-semibold text-gray-800 dark:text-slate-200 mb-4">
              {userRating ? t('showProduct:reviews.updateYourReview') : t('showProduct:reviews.writeAReview')}
            </h3>

            {!isAuth ? (
              <div className="bg-gray-50 dark:bg-[#1C2638] border border-gray-200 dark:border-slate-700 rounded-xl p-4">
                <p className="text-gray-600 dark:text-slate-400 text-sm">{t('showProduct:reviews.loginToWriteReview')}</p>
              </div>
            ) : (
              <>
                <div className="mb-4">
                  <label className="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">
                    {t('showProduct:reviews.yourRating')}
                  </label>
                  <StarRating
                    rating={newReviewRating}
                    onRatingChange={handleRatingSubmit}
                    interactive={true}
                    size="lg"
                    allowHalf={false}
                  />
                </div>

                <div className="mb-4">
                  <label className="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">
                    {t('showProduct:reviews.yourReview')}
                  </label>
                  <textarea
                    value={newReviewComment}
                    onChange={(e) => setNewReviewComment(e.target.value)}
                    placeholder={t('showProduct:reviews.yourReviewPlaceholder')}
                    className="w-full p-3 bg-white dark:bg-[#1C2638] border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-500 rounded-xl resize-none focus:ring-2 focus:ring-[#B12C2E]/20 outline-none focus:border-[#B12C2E] text-sm"
                    rows={4}
                    maxLength={1000}
                  />
                  <p className="text-xs text-gray-500 dark:text-slate-400 mt-1">
                    {newReviewComment.length}/1000 {t('showProduct:reviews.characters')}
                  </p>
                </div>

                <button
                  onClick={handleSubmitReview}
                  disabled={!newReviewComment.trim() || submitting}
                  className="bg-[#B12C2E] hover:bg-[#972426] text-white px-8 py-2.5 rounded-xl font-semibold cursor-pointer transition-colors disabled:bg-gray-300 dark:disabled:bg-slate-800 disabled:cursor-not-allowed flex items-center space-x-2 shadow-sm"
                >
                  {submitting ? (
                    <>
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                      <span>{t('showProduct:reviews.submitting')}</span>
                    </>
                  ) : (
                    <span>{userRating ? t('showProduct:reviews.updateReview') : t('showProduct:reviews.submitReview')}</span>
                  )}
                </button>
              </>
            )}
          </div>
        </div>

        {/* Right Side - Individual Reviews */}
        <div className="space-y-6">
          {loadingReviews ? (
            <div className="text-center py-8">
              <p className="text-gray-500 dark:text-slate-400">{t('showProduct:reviews.loadingReviews')}</p>
            </div>
          ) : reviews.length === 0 ? (
            <div className="text-center py-8 border border-dashed border-gray-300 dark:border-slate-700 rounded-2xl text-gray-500 dark:text-slate-400">
              <p>{t('showProduct:reviews.noReviews')}</p>
            </div>
          ) : (
            reviews.map((review) => (
              <div key={review.id} className="border-b border-gray-100 dark:border-slate-800/80 pb-6 last:border-b-0">
                <div className="flex items-center space-x-3 mb-3">
                  <div className="w-10 h-10 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-white rounded-full flex items-center justify-center font-bold text-sm">
                    {getInitials(review.user.full_name)}
                  </div>
                  <div>
                    <p className="font-semibold text-gray-900 dark:text-slate-100 text-sm">{review.user.full_name}</p>
                    <div className="flex items-center space-x-2 mt-0.5">
                      <StarRating rating={review.rating} size="sm" allowHalf={false} />
                      <span className="text-xs text-gray-400 dark:text-slate-500">{formatDate(review.created_at)}</span>
                    </div>
                  </div>
                </div>

                <p className="text-gray-700 dark:text-slate-300 text-sm leading-relaxed">{review.comment}</p>
              </div>
            ))
          )}

          {/* Pagination Controls */}
          {reviews.length > 0 && lastPage > 1 && (
            <div className="flex justify-center items-center mt-8 px-2">
              <div className="flex items-center space-x-1 sm:space-x-2">
                <button
                  onClick={() => {
                    if (!loadingReviews && currentPage > 1) setCurrentPage(1);
                  }}
                  disabled={currentPage === 1 || loadingReviews}
                  className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                >
                  {lang === 'en' ? (
                    <MdKeyboardDoubleArrowLeft className="w-4 h-4" />
                  ) : (
                    <MdKeyboardDoubleArrowRight className="w-4 h-4" />
                  )}
                </button>

                <button
                  onClick={() => {
                    if (!loadingReviews && currentPage > 1) setCurrentPage(currentPage - 1);
                  }}
                  disabled={currentPage === 1 || loadingReviews}
                  className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                >
                  {lang === 'en' ? (
                    <RiArrowLeftSLine className="w-4 h-4" />
                  ) : (
                    <RiArrowRightSLine className="w-4 h-4" />
                  )}
                </button>

                <span className="px-3 py-1.5 text-xs sm:text-sm bg-[#B12C2E]/10 dark:bg-red-950/40 border border-[#B12C2E]/20 rounded-lg text-[#B12C2E] dark:text-[#E03E41] font-semibold">
                  {t('showProduct:reviews.page')} {currentPage} {t('showProduct:reviews.of')} {lastPage}
                </span>

                <button
                  onClick={() => {
                    if (!loadingReviews && currentPage < lastPage) setCurrentPage(currentPage + 1);
                  }}
                  disabled={currentPage === lastPage || loadingReviews}
                  className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                >
                  {lang === 'en' ? (
                    <RiArrowRightSLine className="w-4 h-4" />
                  ) : (
                    <RiArrowLeftSLine className="w-4 h-4" />
                  )}
                </button>

                <button
                  onClick={() => {
                    if (!loadingReviews && currentPage < lastPage) setCurrentPage(lastPage);
                  }}
                  disabled={currentPage === lastPage || loadingReviews}
                  className="p-2 cursor-pointer bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded-lg disabled:opacity-40 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                >
                  {lang === 'en' ? (
                    <MdKeyboardDoubleArrowRight className="w-4 h-4" />
                  ) : (
                    <MdKeyboardDoubleArrowLeft className="w-4 h-4" />
                  )}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
