<?php

function transform_flight_record($flight)
{
    return [
        "flight_number" => $flight["number"] ?? "",
        "call_sign" => $flight["callSign"] ?? "",

        "status" => $flight["status"] ?? "",
        "codeshare_status" => $flight["codeshareStatus"] ?? "",
        "is_cargo" => !empty($flight["isCargo"]) ? 1 : 0,

        "airline" => $flight["airline"]["name"] ?? "",
        "airline_iata" => $flight["airline"]["iata"] ?? "",

        "aircraft_model" => $flight["aircraft"]["model"] ?? "",
        "aircraft_registration" => $flight["aircraft"]["reg"] ?? "",

        "departure_airport" => $flight["departure"]["airport"]["iata"] ?? "",
        "departure_city" => $flight["departure"]["airport"]["municipalityName"] ?? "",
        "departure_terminal" => $flight["departure"]["terminal"] ?? "",
        "departure_time" => isset($flight["departure"]["scheduledTime"]["local"])
            ? date("Y-m-d H:i:s", strtotime($flight["departure"]["scheduledTime"]["local"]))
            : null,
        "arrival_airport" => $flight["arrival"]["airport"]["iata"] ?? "",
        "arrival_city" => $flight["arrival"]["airport"]["municipalityName"] ?? "",
        "arrival_terminal" => $flight["arrival"]["terminal"] ?? "",
        "arrival_time" => isset($flight["arrival"]["scheduledTime"]["local"])
            ? date("Y-m-d H:i:s", strtotime($flight["arrival"]["scheduledTime"]["local"]))
            : null,
        "distance_km" => $flight["greatCircleDistance"]["km"] ?? null
    ];
}

function search_flights($flight_number, &$errors = [])
{
    $response = api_get(
        "https://aerodatabox.p.rapidapi.com/flights/number/" . urlencode($flight_number),
        [
            "withAircraftImage" => "false",
            "withLocation" => "false",
            "withFlightPlan" => "false"
        ],
        [
            "key_name" => "FLIGHT_API_KEY",
            "host_name" => "FLIGHT_API_HOST"
        ]
    );

    $data = decode_api_response($response, null, $errors);

    if (!$data) {
        return [];
    }

    $rows = [];

    foreach ($data as $flight) {
        $rows[] = transform_flight_record($flight);
    }

    return $rows;
}
