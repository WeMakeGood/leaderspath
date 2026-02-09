/**
 * Custom hook to fetch lesson activities for VB preview.
 *
 * Fetches lesson activities via the LeadersPath REST API to display
 * in the Visual Builder. Falls back to the first available lesson
 * when no specific lesson context is available.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { useState, useEffect, useRef } from 'react';

export interface LessonActivity {
  id: number;
  title: string;
  url: string;
}

export interface LessonActivitiesData {
  lesson_id: number;
  lesson_title: string;
  activities: LessonActivity[];
}

interface UseLessonActivitiesResult {
  /**
   * The lesson activities data.
   */
  data: LessonActivitiesData | null;

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
 * Fetches lesson activities from the LeadersPath REST API.
 *
 * This hook fetches lesson activities for displaying in the VB.
 * It automatically falls back to the first available lesson when
 * no specific lesson context is available (e.g., in Theme Builder).
 *
 * @since 0.1.0
 *
 * @returns Object containing data, isLoading, and error state.
 */
export function useLessonActivities(): UseLessonActivitiesResult {
  const [data, setData] = useState<LessonActivitiesData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  // Track mounted state to avoid setting state on unmounted component.
  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;

    fetchLessonActivities();

    return () => {
      isMountedRef.current = false;
    };
  }, []);

  /**
   * Fetch the lesson activities from REST API.
   */
  async function fetchLessonActivities(): Promise<void> {
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

      // Use the fallback endpoint that returns first lesson as sample data.
      const url = new URL(
        `${wpApiSettings.root}leaderspath/v1/lessons/activities`,
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
        err instanceof Error ? err.message : 'Failed to load lesson activities';
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
