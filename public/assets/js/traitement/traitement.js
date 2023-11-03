const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener("mouseenter", Swal.stopTimer);
    toast.addEventListener("mouseleave", Swal.resumeTimer);
  },
});

$(document).ready(function () {
  console.clear();
  var table = $("#datatables_gestion_pointages").DataTable({
    lengthMenu: [
      [10, 15, 25, 50, 100, 20000000000000],
      [10, 15, 25, 50, 100, "All"],
    ],
    order: [[2, "desc"]],
    ajax: "/pointage/list",
    processing: true,
    serverSide: true,
    deferRender: true,
    language: {
      url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    },
    preDrawCallback: function(settings) {
        if ($.fn.DataTable.isDataTable('#datatables_gestion_pointages')) {
            var dt = $('#datatables_gestion_pointages').DataTable();

            //Abort previous ajax request if it is still in process.
            var settings = dt.settings();
            if (settings[0].jqXHR) {
                settings[0].jqXHR.abort();
            }
        }
    },
    // drawCallback: function () {
    //     $("body tr#" + id_seance).addClass('active_databales');
    // },
    // "columnDefs": [
    //         {
    //             "targets": [11], // The column index you want to hide (zero-based index)
    //             "visible": false, // Hide the column
    //             "searchable": false // Exclude the column from search
    //         }
    //     ]
  });
  $("select").select2();

  $("#day").on("change", async function () {
    const day = $(this).val();
    if (day || day != "") {
      table.columns(0).search(day).draw();
    } else {
      table.columns(0).search("").draw();
    }
  });
  $("#hdebut").on("change", async function () {
    const hdebut = $(this).val();
    if (hdebut || hdebut != "") {
      table.columns(1).search(hdebut).draw();
    } else {
      table.columns(1).search("").draw();
    }
  });
  $("#hfin").on("change", async function () {
    const hfin = $(this).val();
    if (hfin || hfin != "") {
      table.columns(2).search(hfin).draw();
    } else {
      table.columns(2).search("").draw();
    }
  });
  
  $('body').on('click', '#import', async function(e){
      e.preventDefault();
      var day = $("body #day").val();
      if(!day) {
          Toast.fire({
              icon: 'error',
              title: 'Veuillez selection une date!',
          })
          return;
      }
      console.log(day)
      const icon = $("#import i");
      let formData = new FormData();
      formData.append('date',day)
      icon.remove('fa-check').addClass("fa-spinner fa-spin");
      Importation(formData,icon)
      
      // window.open('/assiduite/traitement/planing/'+day, '_blank');
  })

  // $('body').on('click', '#extraction_resi', async function(e){
  //     e.preventDefault();
  //     var day = $("body #day").val();
  //     if(!day) {
  //         Toast.fire({
  //             icon: 'error',
  //             title: 'Veuillez selection une date!',
  //         })
  //         return;
  //     }
  //     console.log(day)
  //     const icon = $("#import i");
  //     let formData = new FormData();
  //     formData.append('date',day)
  //     icon.remove('fa-check').addClass("fa-spinner fa-spin");
  //     Importation(formData,icon)
      
  //     // window.open('/assiduite/traitement/planing/'+day, '_blank');
  // })

  $("#extraction_resi").on("click",function(e) {
    e.preventDefault()
      
      const date_debut = $("body #date_debut").val();
      const date_fin = $("body #date_fin").val();
      console.log(date_debut);
      console.log(date_fin);
    if(!date_debut || !date_fin ){
      Toast.fire({
        icon: 'error',
        title: 'Veuillez remplire tous les champs!',
      })
      return;
    }
            
    window.open(
      "/pointage/extractionResidanat/"+date_debut+"/"+date_fin,
      "_blank"
    );

  })


  
  const Importation = async (formData,icon) => {
    try {
      const request = await axios.post('/pointage/importation',formData);
      const response = request.data;
      console.log(response);
      table.ajax.reload();
      Toast.fire({
        icon: 'success',
        title: response,
      })
    } catch (error) {
      console.log(error.response);
      const message = error.response;
      Toast.fire({
          icon: 'error',
          title: message,
      })
    }
    icon.addClass('fa-check').removeClass("fa-spinner fa-spin");
  }

  
  const ImportationSync = async () => {
    console.log('ImportationSync ');
    var date = new Date();
    let formData = new FormData();
    formData.append('date',"")
    try {
        const request = await axios.post('/pointage/importation',formData);
        let response = request.data
        console.log(date)
        ImportationSync()
        console.log(response);
    } catch (error) {
        const message = error.response.data;
        console.log('Error Importation ------')
        console.log(message)
    }
  }
  ImportationSync()
  // window.setInterval(function(){ // Set interval for checking
  //     // var date = new Date(); // Create a Date object to find out what time it is
  
  //     // if(date.getHours() === 2 && date.getMinutes()  === 0){ // Check the time
  //       ImportationPointages() 
  //     // }       
  // }, 1200000);

});
