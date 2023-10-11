$(document).ready(function () {
  
  // alert('test');
  
  const ImportationPointages = async () => {
      console.log('ImportationPointages ');
      var date = new Date();
      console.log(date)
      try {
      const request = await axios.post('/importPointeuse');
      let response = request.data
      console.log(response);
      } catch (error) {
          const message = error.response.data;
          console.log('Error Importation ------')
          console.log(message)
      }
  }
  // ImportationPointages()
    // ImportationPointages() 
    // window.setInterval(function(){ // Set interval for checking
    //     // var date = new Date(); // Create a Date object to find out what time it is
    
    //     // if(date.getHours() === 2 && date.getMinutes()  === 0){ // Check the time
    //       ImportationPointages() 
    //     // }       
    // }, 1200000);
});
