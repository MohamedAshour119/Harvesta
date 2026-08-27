export const triggerLanguageSwitch = (targetLanguage: string) => {
  window.dispatchEvent(
    new CustomEvent('harvesta:switch-language', {
      detail: { language: targetLanguage },
    })
  );
};
