/**
 * Utility for Multi-Language Translation (Google Translate Client Bridge)
 */

export const SUPPORTED_LANGUAGES = [
  { code: 'vi', label: 'Tiếng Việt', native: 'Tiếng Việt', flag: '🇻🇳', short: 'VN' },
  { code: 'en', label: 'Tiếng Anh', native: 'English', flag: '🇬🇧', short: 'EN' },
  { code: 'zh-CN', label: 'Tiếng Trung', native: '中文 (简体)', flag: '🇨🇳', short: 'CN' },
  { code: 'ko', label: 'Tiếng Hàn', native: '한국어', flag: '🇰🇷', short: 'KR' },
  { code: 'ja', label: 'Tiếng Nhật', native: '日本語', flag: '🇯🇵', short: 'JP' },
  { code: 'fr', label: 'Tiếng Pháp', native: 'Français', flag: '🇫🇷', short: 'FR' },
];

export function getCurrentLanguage() {
  if (typeof window === 'undefined') return 'vi';
  return localStorage.getItem('nhaxekientruc_locale') || localStorage.getItem('bushub_locale') || 'vi';
}

export function getLanguageInfo(code) {
  return SUPPORTED_LANGUAGES.find(l => l.code === code) || SUPPORTED_LANGUAGES[0];
}

export function setWebsiteLanguage(langCode) {
  if (typeof window === 'undefined') return;

  const validLang = SUPPORTED_LANGUAGES.find(l => l.code === langCode);
  const targetCode = validLang ? validLang.code : 'vi';

  localStorage.setItem('nhaxekientruc_locale', targetCode);
  localStorage.setItem('bushub_locale', targetCode);

  if (targetCode === 'vi') {
    // Xóa cookie googtrans để trở về tiếng Việt gốc
    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname + ';';
    document.cookie = 'googtrans=/vi/vi; path=/;';
    document.cookie = 'googtrans=/vi/vi; path=/; domain=' + window.location.hostname + ';';

    const combo = document.querySelector('.goog-te-combo');
    if (combo) {
      combo.value = 'vi';
      combo.dispatchEvent(new Event('change'));
    }
    // Reload sau 100ms để khôi phục DOM sạch sẽ
    setTimeout(() => {
      window.location.reload();
    }, 150);
    return;
  }

  // Đặt cookie dịch sang ngôn ngữ đích
  document.cookie = `googtrans=/vi/${targetCode}; path=/;`;
  document.cookie = `googtrans=/vi/${targetCode}; path=/; domain=${window.location.hostname};`;

  const combo = document.querySelector('.goog-te-combo');
  if (combo) {
    combo.value = targetCode;
    combo.dispatchEvent(new Event('change'));
  } else {
    // Reload để Google Translate đọc cookie và dịch toàn bộ website
    setTimeout(() => {
      window.location.reload();
    }, 150);
  }
}
