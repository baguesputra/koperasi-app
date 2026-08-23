export function formatPhone(phone) {
  if (!phone) return '';
  
  let cleaned = phone.replace(/\D/g, '');
  
  if (cleaned.startsWith('62')) {
    return cleaned;
  }
  
  if (cleaned.startsWith('0')) {
    return '62' + cleaned.substring(1);
  }
  
  if (cleaned.startsWith('8')) {
    return '62' + cleaned;
  }
  
  return '62' + cleaned;
}

export function isValidIndonesianPhone(phone) {
  const formatted = formatPhone(phone);
  return /^628\d{8,11}$/.test(formatted);
}