export const triggerLanguageSwitch = (targetLanguage: string) => {
  window.dispatchEvent(
    new CustomEvent('cairo3a:switch-language', {
      detail: { language: targetLanguage },
    })
  );
};
