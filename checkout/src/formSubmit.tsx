let formSubmit = false;

export const getFormSubmit = () => {
  return formSubmit;
}

export const setFormSubmit = (value: boolean) => {
  formSubmit = value;
}