import PhoneInput from 'react-phone-input-2'
import 'react-phone-input-2/lib/style.css'

interface PhoneInputProps {
  id: string;
  name: string;
  label: string;
  value: string | null | undefined;
  onChange: (e: React.ChangeEvent<HTMLInputElement> | string) => void;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
}
    
export default function PhoneInputComponent({
  id,
  name,
  label,
  value,
  onChange,
  placeholder,
  required,
  disabled,
  error,
}: PhoneInputProps) {
  return (
    <div className="space-y-2 phone-input-container">
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </label>
        <PhoneInput
          placeholder={placeholder}
          country={'eg'}
          value={value as string}
          onChange={(phoneValue) => onChange && onChange(phoneValue || '')}
          disabled={disabled}
          inputClass="custom-phone-input"
          buttonClass="custom-phone-input-button"
        />
        {error && (
          <p className="text-red-500 text-sm mt-1">
            {error}
          </p>
        )}
    </div>
  )
}
