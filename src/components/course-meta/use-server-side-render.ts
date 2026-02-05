/**
 * Custom hook to fetch course meta data for VB preview.
 *
 * Fetches course metadata via the LeadersPath REST API to display
 * in the Visual Builder. Falls back to the first available course
 * when no specific course context is available.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { useState, useEffect, useRef } from 'react';

export interface CourseMeta {
  course_id: number;
  course_title: string;
  duration: string;
  difficulty: string;
  difficulty_name: string;
  activity_count: number;
  is_sample: boolean;
}

interface UseCourseMetaResult {
  /**
   * The course metadata.
   */
  data: CourseMeta | null;

  /**
   * Whether the content is currently loading.
   */
  isLoading: boolean;

  /**
   * Error message if the fetch failed.
   */
  error: string | null;
}

/**
 * Fetches course metadata from the LeadersPath REST API.
 *
 * This hook fetches course meta data for displaying in the VB.
 * It automatically falls back to the first available course when
 * no specific course context is available (e.g., in Theme Builder).
 *
 * @since 0.1.0
 *
 * @returns Object containing data, isLoading, and error state.
 */
export function useCourseMeta(): UseCourseMetaResult {
  const [data, setData] = useState<CourseMeta | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  // Track mounted state to avoid setting state on unmounted component.
  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;

    fetchCourseMeta();

    return () => {
      isMountedRef.current = false;
    };
  }, []);

  /**
   * Fetch the course metadata from REST API.
   */
  async function fetchCourseMeta(): Promise<void> {
    if (!isMountedRef.current) {
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      // Get the REST API URL and nonce from WordPress globals.
      const wpApiSettings = (window as WindowWithWpApi).wpApiSettings || {
        root: '/wp-json/',
        nonce: '',
      };

      // Use the fallback endpoint that returns first course as sample data.
      const url = new URL(
        `${wpApiSettings.root}leaderspath/v1/courses/meta`,
        window.location.origin
      );

      const response = await fetch(url.toString(), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wpApiSettings.nonce,
        },
        credentials: 'same-origin',
      });

      if (!isMountedRef.current) {
        return;
      }

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(
          errorData.message || `HTTP error ${response.status}`
        );
      }

      const responseData = await response.json();

      if (!isMountedRef.current) {
        return;
      }

      setData(responseData);
      setError(null);
    } catch (err) {
      if (!isMountedRef.current) {
        return;
      }

      const errorMessage =
        err instanceof Error ? err.message : 'Failed to load course data';
      setError(errorMessage);
      setData(null);
    } finally {
      if (isMountedRef.current) {
        setIsLoading(false);
      }
    }
  }

  return { data, isLoading, error };
}

/**
 * Window interface extended with WordPress API settings.
 */
interface WindowWithWpApi extends Window {
  wpApiSettings?: {
    root: string;
    nonce: string;
  };
}
