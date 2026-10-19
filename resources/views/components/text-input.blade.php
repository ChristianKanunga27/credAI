@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-green-500 dark:focus:ring-green-400 rounded-md shadow-sm']) }}>
