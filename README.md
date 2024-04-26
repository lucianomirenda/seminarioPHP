Trabajo para la materia: Seminario de Lenguajes opción PHP, React y API Rest

Se desea desarrollar una aplicación web que permita gestionar alquileres temporarios de una
inmobiliaria.
Un empleado debe poder administrar las propiedades de la inmobiliaria y llevar el control de las
reservas, así como también de las personas que las realizan (inquilinos).
Los nombres de las localidades y de los tipos de propiedades no se pueden repetir.
El documento de un inquilino no se puede repetir.
En cada reserva, el valor total es la multiplicación del valor de una propiedad por una noche por la
cantidad de noches de la reserva.
Una reserva solo se puede realizar si el inquilino está activo y la propiedad está disponible.
Una reserva solo se puede editar o eliminar si no comenzó (fecha_desde es menor a la fecha actual)
